<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\GetRolePermissionMatrix;

use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\RoleModulePermissionRepository;
use ModularizeRbac\Core\Application\Ports\RoleRepository;
use ModularizeRbac\Core\Application\Role\RoleOutput;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\NotFound;

/**
 * Read-only use-case: returns a role with its complete module ×
 * permission matrix in a single call. Replaces the inline
 * `enrich()` that the v1 Laravel controller did by composing
 * RoleRepository + RoleModulePermissionRepository + ModuleRepository
 * across multiple queries.
 *
 * Authorization: `admin.roles.view`.
 *
 * The output payload is shaped for direct HTTP/UI consumption:
 * sorted by module sort_order (then slug), with each row carrying
 * the canonical five-flag tuple.
 */
final class GetRolePermissionMatrix
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly RoleModulePermissionRepository $bindings,
        private readonly Authorizer $authorizer,
    ) {
    }

    public function execute(string $rawId): GetRolePermissionMatrixOutput
    {
        $this->authorizer->ensure('admin.roles.view');

        $id = new Uuid($rawId);
        $role = $this->roles->find($id) ?? throw NotFound::of('Role', $id->value);

        $rows = $this->bindings->matrixFor($id);
        usort($rows, static function (RolePermissionMatrixRow $a, RolePermissionMatrixRow $b): int {
            if ($a->module->sortOrder() !== $b->module->sortOrder()) {
                return $a->module->sortOrder() <=> $b->module->sortOrder();
            }

            return $a->module->slug()->value <=> $b->module->slug()->value;
        });

        $modules = [];
        foreach ($rows as $row) {
            $modules[] = new RoleModulePermissionEntry(
                moduleId: $row->module->id->value,
                modulePermissionId: $row->permission->id->value,
                slug: $row->module->slug()->value,
                name: $row->module->name(),
                isListingAllowed: $row->permission->isListingAllowed(),
                isReadingAllowed: $row->permission->isReadingAllowed(),
                isWritingAllowed: $row->permission->isWritingAllowed(),
                isEditingAllowed: $row->permission->isEditingAllowed(),
                isDeleteAllowed: $row->permission->isDeleteAllowed(),
            );
        }

        return new GetRolePermissionMatrixOutput(
            role: RoleOutput::fromEntity($role),
            modules: $modules,
        );
    }
}
