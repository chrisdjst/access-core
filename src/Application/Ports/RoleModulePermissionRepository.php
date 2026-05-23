<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use ModularizeRbac\Core\Domain\Module\ModulePermission;
use ModularizeRbac\Core\Domain\RoleModulePermission\RoleModulePermission;
use ModularizeRbac\Core\Domain\Shared\Uuid;

interface RoleModulePermissionRepository
{
    /**
     * Return every binding for a role across all modules, hydrated
     * with their current ModulePermission flag set. Used by both the
     * read paths (the role detail screen) and the synchronizer to
     * compute deltas.
     *
     * @return list<array{binding: RoleModulePermission, permission: ModulePermission}>
     */
    public function forRole(Uuid $roleId): array;

    public function findByRoleAndModule(Uuid $roleId, Uuid $moduleId): ?RoleModulePermission;

    public function save(RoleModulePermission $binding): void;

    public function saveModulePermission(ModulePermission $permission): void;

    public function delete(RoleModulePermission $binding): void;
}
