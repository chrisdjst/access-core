<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Role\SyncRoleModules;

use Modularize\Access\Application\Ports\Authorizer;
use Modularize\Access\Application\Ports\DomainEventDispatcher;
use Modularize\Access\Application\Ports\ExternalPermissionGateway;
use Modularize\Access\Application\Ports\ModuleRepository;
use Modularize\Access\Application\Ports\RoleModulePermissionRepository;
use Modularize\Access\Application\Ports\RoleRepository;
use Modularize\Access\Application\Ports\UnitOfWork;
use Modularize\Access\Application\Role\RoleOutput;
use Modularize\Access\Domain\Events\RolePermissionsChanged;
use Modularize\Access\Domain\Module\Module;
use Modularize\Access\Domain\Module\ModulePermission;
use Modularize\Access\Domain\Role\Role;
use Modularize\Access\Domain\RoleModulePermission\RoleModulePermission;
use Modularize\Access\Domain\RoleModulePermission\RoleModulePermissionSynchronizer;
use Modularize\Access\Domain\Shared\Clock;
use Modularize\Access\Domain\Shared\IdGenerator;
use Modularize\Access\Exceptions\InvalidInput;
use Modularize\Access\Exceptions\NotFound;

/**
 * Use-case: replace the complete module-permission matrix for a role
 * in a single atomic operation. This is the application-layer
 * orchestrator of the legacy `RoleModulePermissionObserver::sync()`
 * algorithm: pre-computes the grant/revoke plan, applies the
 * persistence diff (creating bindings, deactivating bindings no
 * longer in the payload), and emits one `RolePermissionsChanged`
 * event per affected module so subscribers (e.g. the Spatie adapter)
 * can replicate the change.
 *
 * Crucial: bindings absent from the payload are REMOVED. The legacy
 * controller relied on the same "kept ids" semantic.
 *
 * Authorization: `admin.roles.update`.
 */
final class SyncRoleModules
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

    public function execute(SyncRoleModulesInput $input): RoleOutput
    {
        $this->authorizer->ensure('admin.roles.update');

        $role = $this->roles->find($input->roleId) ?? throw NotFound::of('Role', $input->roleId->value);

        // Pre-validate that every module referenced exists. Failing
        // fast outside the transaction means a bad payload never
        // touches the DB.
        $modulesById = [];
        foreach ($input->entries as $i => $entry) {
            $module = $this->modules->find($entry->moduleId);
            if ($module === null) {
                throw InvalidInput::of(
                    "modules.{$i}.module_id",
                    "Module not found: {$entry->moduleId->value}"
                );
            }
            $modulesById[$entry->moduleId->value] = $module;
        }

        $emittedEvents = $this->uow->transactional(
            fn (): array => $this->applyDiff($role, $input, $modulesById),
        );

        foreach ($emittedEvents as $event) {
            $this->events->dispatch($event);
        }

        return RoleOutput::fromEntity($role);
    }

    /**
     * @param  array<string, Module>  $modulesById
     * @return list<RolePermissionsChanged>
     */
    private function applyDiff(Role $role, SyncRoleModulesInput $input, array $modulesById): array
    {
        $actorId = $this->authorizer->actorId();
        $now = $this->clock;

        $existing = $this->bindings->forRole($role->id);
        $existingByModuleId = [];
        foreach ($existing as $row) {
            $existingByModuleId[$row['binding']->moduleId->value] = $row;
        }

        $keptModuleIds = [];
        $events = [];

        // Upsert path
        foreach ($input->entries as $entry) {
            $keptModuleIds[$entry->moduleId->value] = true;
            $module = $modulesById[$entry->moduleId->value];
            $slug = $module->slug();

            $newPermission = ModulePermission::create(
                id: $this->ids->nextUuid(),
                isListingAllowed: $entry->isListingAllowed,
                isReadingAllowed: $entry->isReadingAllowed,
                isWritingAllowed: $entry->isWritingAllowed,
                isEditingAllowed: $entry->isEditingAllowed,
                isDeleteAllowed: $entry->isDeleteAllowed,
                createdBy: $actorId,
                clock: $now,
            );
            $this->bindings->saveModulePermission($newPermission);

            if (isset($existingByModuleId[$entry->moduleId->value])) {
                $binding = $existingByModuleId[$entry->moduleId->value]['binding'];
                $binding->pointToPermission($newPermission->id, $actorId, $now);
            } else {
                $binding = RoleModulePermission::create(
                    id: $this->ids->nextUuid(),
                    roleId: $role->id,
                    moduleId: $module->id,
                    modulePermissionId: $newPermission->id,
                    createdBy: $actorId,
                    clock: $now,
                );
            }
            $this->bindings->save($binding);

            // Compute Spatie-style grant/revoke diff for this module.
            $current = $this->external->permissionsHeldBy($role->id, $role->guard());
            $plan = $this->sync->diff($slug, $newPermission, $current);
            if (! $plan->isNoop()) {
                $this->external->applyPlan($role->id, $role->guard(), $plan->toGrant, $plan->toRevoke);
                $events[] = new RolePermissionsChanged(
                    roleId: $role->id,
                    guard: $role->guard(),
                    moduleId: $module->id,
                    granted: $plan->toGrant,
                    revoked: $plan->toRevoke,
                    occurredAt: $now->now(),
                );
            }
        }

        // Removal path
        foreach ($existing as $row) {
            $binding = $row['binding'];
            if (isset($keptModuleIds[$binding->moduleId->value])) {
                continue;
            }
            $module = $this->modules->find($binding->moduleId);
            if ($module === null) {
                // Tolerate orphan bindings — just drop them.
                $this->bindings->delete($binding);
                continue;
            }

            $slug = $module->slug();
            $current = $this->external->permissionsHeldBy($role->id, $role->guard());
            $toRevoke = $this->sync->permissionsToRevokeOnUnbind($slug, $current);

            $this->bindings->delete($binding);

            if ($toRevoke !== []) {
                $this->external->applyPlan($role->id, $role->guard(), [], $toRevoke);
                $events[] = new RolePermissionsChanged(
                    roleId: $role->id,
                    guard: $role->guard(),
                    moduleId: $module->id,
                    granted: [],
                    revoked: $toRevoke,
                    occurredAt: $now->now(),
                );
            }
        }

        return $events;
    }
}
