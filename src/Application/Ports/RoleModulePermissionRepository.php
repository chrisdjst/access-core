<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Ports;

use Modularize\Access\Domain\Module\ModulePermission;
use Modularize\Access\Domain\RoleModulePermission\RoleModulePermission;
use Modularize\Access\Domain\Shared\Uuid;

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
