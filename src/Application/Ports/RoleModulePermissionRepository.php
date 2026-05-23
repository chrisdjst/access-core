<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use ModularizeRbac\Core\Application\Role\GetRolePermissionMatrix\RolePermissionMatrixRow;
use ModularizeRbac\Core\Domain\Module\ModulePermission;
use ModularizeRbac\Core\Domain\RoleModulePermission\RoleModulePermission;
use ModularizeRbac\Core\Domain\Shared\Uuid;

interface RoleModulePermissionRepository
{
    /**
     * Return every binding for a role across all modules, hydrated
     * with their current ModulePermission flag set. Used by the
     * synchronizer to compute deltas.
     *
     * @return list<array{binding: RoleModulePermission, permission: ModulePermission}>
     */
    public function forRole(Uuid $roleId): array;

    /**
     * Optimized read for the role-permission matrix UI: returns one
     * row per bound module with binding + flag set + module aggregate
     * fully hydrated. Adapters are encouraged to fulfill this with a
     * single joined query.
     *
     * @return list<RolePermissionMatrixRow>
     */
    public function matrixFor(Uuid $roleId): array;

    public function findByRoleAndModule(Uuid $roleId, Uuid $moduleId): ?RoleModulePermission;

    public function save(RoleModulePermission $binding): void;

    public function saveModulePermission(ModulePermission $permission): void;

    public function delete(RoleModulePermission $binding): void;
}
