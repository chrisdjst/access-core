<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\DeleteRole;

use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\RoleModulePermissionRepository;
use ModularizeRbac\Core\Application\Ports\RoleRepository;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Exceptions\NotFound;

/**
 * Use-case: soft-delete a role.
 *
 * Authorization: `admin.roles.delete`.
 *
 * Two invariants enforced here:
 *
 *   1. System roles (`isSystem = true`) cannot be deleted through
 *      this use-case. Hosts that need to retire a seeded role do it
 *      via a migration, not via the admin UI.
 *
 *   2. Roles with active module-permission bindings cannot be
 *      deleted. The caller must first issue a SyncRoleModules with
 *      `modules: []` to drop the bindings explicitly — that keeps
 *      audit trails clean and prevents accidental cascade.
 *
 * v2.8 changed the semantic from HARD delete to SOFT delete: the row
 * stays in the database with `deletedAt` set, so a follow-up
 * {@see \ModularizeRbac\Core\Application\Role\RestoreRole\RestoreRole}
 * can undo the action. Hosts that need true purge keep using the
 * port's `delete()` method directly.
 */
final class DeleteRole
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly RoleModulePermissionRepository $bindings,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
        private readonly Clock $clock,
    ) {
    }

    public function execute(string $rawId): void
    {
        $this->authorizer->ensure('admin.roles.delete');

        $id = new Uuid($rawId);
        $role = $this->roles->find($id) ?? throw NotFound::of('Role', $id->value);

        if ($role->isSystem()) {
            throw InvalidInput::of('id', 'System roles cannot be deleted.');
        }

        if ($this->bindings->forRole($id) !== []) {
            throw InvalidInput::of(
                'id',
                'Role still has module-permission bindings. Call SyncRoleModules with an empty `modules` array to drop them first.',
            );
        }

        $this->uow->transactional(function () use ($role): void {
            $role->softDelete($this->clock);
            $this->roles->softDelete($role);
        });
    }
}
