<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\CloneRole;

use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\DomainEventDispatcher;
use ModularizeRbac\Core\Application\Ports\ExternalPermissionGateway;
use ModularizeRbac\Core\Application\Ports\ModuleRepository;
use ModularizeRbac\Core\Application\Ports\RoleModulePermissionRepository;
use ModularizeRbac\Core\Application\Ports\RoleRepository;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Application\Role\RoleOutput;
use ModularizeRbac\Core\Domain\Events\RolePermissionsChanged;
use ModularizeRbac\Core\Domain\Module\ModulePermission;
use ModularizeRbac\Core\Domain\Role\Role;
use ModularizeRbac\Core\Domain\RoleModulePermission\RoleModulePermission;
use ModularizeRbac\Core\Domain\RoleModulePermission\RoleModulePermissionSynchronizer;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\IdGenerator;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Exceptions\NotFound;

/**
 * Use-case: produce a new role with the same module-permission matrix
 * as an existing one. Useful for "spin up a variant of this role" flows
 * in the admin UI where copying then tweaking is faster than rebuilding
 * the binding list from scratch.
 *
 * Authorization: `admin.roles.create` (creates a fresh role).
 *
 * Semantics:
 *  - guard, tenant, and level are inherited from the source.
 *  - `isSystem` is always `false` for the clone (see {@see CloneRoleInput}).
 *  - Each source binding is mirrored: a brand-new `ModulePermission`
 *    row is created with the same flags, and a new `RoleModulePermission`
 *    points at it. Cloning never reuses ids — historical rows on the
 *    source role stay untouched.
 *  - The external (Spatie) gateway is fed one `applyPlan` per cloned
 *    binding so `role_has_permissions` mirrors the new role.
 *  - One `RolePermissionsChanged` event is dispatched per affected
 *    module so the audit log and any subscribers see the new role's
 *    initial grant set.
 */
final class CloneRole
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly ModuleRepository $modules,
        private readonly RoleModulePermissionRepository $bindings,
        private readonly ExternalPermissionGateway $external,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
        private readonly DomainEventDispatcher $events,
        private readonly IdGenerator $ids,
        private readonly Clock $clock,
        private readonly RoleModulePermissionSynchronizer $sync = new RoleModulePermissionSynchronizer(),
    ) {
    }

    public function execute(CloneRoleInput $input): RoleOutput
    {
        $this->authorizer->ensure('admin.roles.create');

        $source = $this->roles->find($input->sourceRoleId)
            ?? throw NotFound::of('Role', $input->sourceRoleId->value);

        if ($this->roles->findByName($input->name, $source->guard(), $source->tenantId()) !== null) {
            throw InvalidInput::of(
                'name',
                sprintf(
                    'A role with name "%s" already exists for guard "%s"%s.',
                    $input->name,
                    $source->guard()->value,
                    $source->tenantId() !== null ? ' in this tenant' : '',
                ),
            );
        }

        [$newRole, $emittedEvents] = $this->uow->transactional(
            fn (): array => $this->cloneRoleAndBindings($source, $input),
        );

        foreach ($emittedEvents as $event) {
            $this->events->dispatch($event);
        }

        return RoleOutput::fromEntity($newRole);
    }

    /**
     * @return array{0: Role, 1: list<RolePermissionsChanged>}
     */
    private function cloneRoleAndBindings(Role $source, CloneRoleInput $input): array
    {
        $actorId = $this->authorizer->actorId();
        $now = $this->clock;

        $newRole = Role::create(
            id: $this->ids->nextUuid(),
            name: $input->name,
            displayName: $input->displayName ?? $source->displayName(),
            guard: $source->guard(),
            tenantId: $source->tenantId(),
            level: $source->level(),
            isSystem: false,
            clock: $now,
            parentRoleId: $source->parentRoleId(),
        );
        $this->roles->save($newRole);

        $events = [];
        foreach ($this->bindings->forRole($source->id) as $row) {
            $sourceBinding = $row['binding'];
            $sourcePermission = $row['permission'];

            $module = $this->modules->find($sourceBinding->moduleId);
            if ($module === null) {
                continue;
            }

            $newPermission = ModulePermission::create(
                id: $this->ids->nextUuid(),
                isListingAllowed: $sourcePermission->isListingAllowed(),
                isReadingAllowed: $sourcePermission->isReadingAllowed(),
                isWritingAllowed: $sourcePermission->isWritingAllowed(),
                isEditingAllowed: $sourcePermission->isEditingAllowed(),
                isDeleteAllowed: $sourcePermission->isDeleteAllowed(),
                createdBy: $actorId,
                clock: $now,
            );
            $this->bindings->saveModulePermission($newPermission);

            $newBinding = RoleModulePermission::create(
                id: $this->ids->nextUuid(),
                roleId: $newRole->id,
                moduleId: $module->id,
                modulePermissionId: $newPermission->id,
                createdBy: $actorId,
                clock: $now,
            );
            $this->bindings->save($newBinding);

            $plan = $this->sync->diff($module->slug(), $newPermission, []);
            if (! $plan->isNoop()) {
                $this->external->applyPlan(
                    $newRole->id,
                    $newRole->guard(),
                    $plan->toGrant,
                    $plan->toRevoke,
                );
                $events[] = new RolePermissionsChanged(
                    roleId: $newRole->id,
                    guard: $newRole->guard(),
                    moduleId: $module->id,
                    granted: $plan->toGrant,
                    revoked: $plan->toRevoke,
                    occurredAt: $now->now(),
                );
            }
        }

        return [$newRole, $events];
    }
}
