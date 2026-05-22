<?php

declare(strict_types=1);

namespace Modularize\Access\Tests\Application\Doubles;

use Modularize\Access\Application\Ports\RoleModulePermissionRepository;
use Modularize\Access\Domain\Module\ModulePermission;
use Modularize\Access\Domain\RoleModulePermission\RoleModulePermission;
use Modularize\Access\Domain\Shared\Uuid;

final class InMemoryRoleModulePermissionRepository implements RoleModulePermissionRepository
{
    /** @var array<string, RoleModulePermission> */
    private array $bindings = [];

    /** @var array<string, ModulePermission> */
    private array $permissions = [];

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
