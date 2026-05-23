<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\GetRolePermissionMatrix;

/**
 * One row in the role-permission matrix: a bound module plus its
 * five canonical action flags as held by the role.
 */
final readonly class RoleModulePermissionEntry
{
    public function __construct(
        public string $moduleId,
        public string $modulePermissionId,
        public string $slug,
        public string $name,
        public bool $isListingAllowed,
        public bool $isReadingAllowed,
        public bool $isWritingAllowed,
        public bool $isEditingAllowed,
        public bool $isDeleteAllowed,
    ) {
    }
}
