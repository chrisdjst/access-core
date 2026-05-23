<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\GetRolePermissionMatrix;

use ModularizeRbac\Core\Application\Role\RoleOutput;

final readonly class GetRolePermissionMatrixOutput
{
    public function __construct(
        public RoleOutput $role,
        /** @var list<RoleModulePermissionEntry> */
        public array $modules,
    ) {
    }
}
