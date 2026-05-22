<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Ports;

use Modularize\Access\Domain\Permission\Permission;
use Modularize\Access\Domain\Permission\PermissionName;
use Modularize\Access\Domain\Role\GuardName;

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
