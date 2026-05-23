<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Tests\Application\Doubles;

use ModularizeRbac\Core\Application\Ports\RoleModulePermissionRepository;
use ModularizeRbac\Core\Application\Role\GetRolePermissionMatrix\RolePermissionMatrixRow;
use ModularizeRbac\Core\Domain\Module\ModulePermission;
use ModularizeRbac\Core\Domain\RoleModulePermission\RoleModulePermission;
use ModularizeRbac\Core\Domain\Shared\Uuid;

final class InMemoryRoleModulePermissionRepository implements RoleModulePermissionRepository
{
    /** @var array<string, RoleModulePermission> */
    private array $bindings = [];

    /** @var array<string, ModulePermission> */
    private array $permissions = [];

    public function __construct(private readonly InMemoryModuleRepository $modules = new InMemoryModuleRepository())
    {
    }

    public function modules(): InMemoryModuleRepository
    {
        return $this->modules;
    }

    public function forRole(Uuid $roleId): array
    {
        $result = [];
        foreach ($this->bindings as $binding) {
            if ($binding->roleId->equals($roleId)) {
                $perm = $this->permissions[$binding->modulePermissionId()->value] ?? null;
                if ($perm === null) {
                    continue;
                }
                $result[] = ['binding' => $binding, 'permission' => $perm];
            }
        }

        return $result;
    }

    public function matrixFor(Uuid $roleId): array
    {
        $rows = [];
        foreach ($this->bindings as $binding) {
            if (! $binding->roleId->equals($roleId)) {
                continue;
            }
            $perm = $this->permissions[$binding->modulePermissionId()->value] ?? null;
            if ($perm === null) {
                continue;
            }
            $module = $this->modules->find($binding->moduleId);
            if ($module === null) {
                continue;
            }
            $rows[] = new RolePermissionMatrixRow(
                binding: $binding,
                permission: $perm,
                module: $module,
            );
        }

        return $rows;
    }

    public function findByRoleAndModule(Uuid $roleId, Uuid $moduleId): ?RoleModulePermission
    {
        foreach ($this->bindings as $binding) {
            if ($binding->roleId->equals($roleId) && $binding->moduleId->equals($moduleId)) {
                return $binding;
            }
        }

        return null;
    }

    public function save(RoleModulePermission $binding): void
    {
        $this->bindings[$binding->id->value] = $binding;
    }

    public function saveModulePermission(ModulePermission $permission): void
    {
        $this->permissions[$permission->id->value] = $permission;
    }

    public function delete(RoleModulePermission $binding): void
    {
        unset($this->bindings[$binding->id->value]);
    }
}
