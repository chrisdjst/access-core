<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use ModularizeRbac\Core\Domain\Permission\Permission;
use ModularizeRbac\Core\Domain\Permission\PermissionName;
use ModularizeRbac\Core\Domain\Role\GuardName;

interface PermissionRepository
{
    public function findByName(PermissionName $name, GuardName $guard): ?Permission;

    /**
     * Find or create a permission identified by (name, guard). Used
     * when the synchronizer needs to ensure a target permission row
     * exists before granting it to a role.
     */
    public function findOrCreate(PermissionName $name, GuardName $guard): Permission;
}
