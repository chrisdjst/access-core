<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\GetRolePermissionMatrix;

use ModularizeRbac\Core\Domain\Module\Module;
use ModularizeRbac\Core\Domain\Module\ModulePermission;
use ModularizeRbac\Core\Domain\RoleModulePermission\RoleModulePermission;

/**
 * Read-row returned by {@see \ModularizeRbac\Core\Application\Ports\RoleModulePermissionRepository::matrixFor()}.
 *
 * A repository implementation can hydrate this from a single joined
 * query (binding × module_permission × module) — the in-memory
 * double does it with three lookups but the contract allows
 * adapters to optimize freely.
 */
final readonly class RolePermissionMatrixRow
{
    public function __construct(
        public RoleModulePermission $binding,
        public ModulePermission $permission,
        public Module $module,
    ) {
    }
}
