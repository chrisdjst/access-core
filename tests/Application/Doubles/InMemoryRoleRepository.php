<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Tests\Application\Doubles;

use ModularizeRbac\Core\Application\Ports\RoleRepository;
use ModularizeRbac\Core\Application\Role\RoleFilter;
use ModularizeRbac\Core\Application\Shared\PaginatedResult;
use ModularizeRbac\Core\Application\Shared\Pagination;
use ModularizeRbac\Core\Domain\Role\GuardName;
use ModularizeRbac\Core\Domain\Role\Role;
use ModularizeRbac\Core\Domain\Shared\Uuid;

final class InMemoryRoleRepository implements RoleRepository
{
    /** @var array<string, Role> */
    private array $byId = [];

    public function find(Uuid $id): ?Role
    {
        return $this->byId[$id->value] ?? null;
    }

    public function search(?GuardName $guard, ?Uuid $tenantId): array
    {
        $result = [];
        foreach ($this->byId as $role) {
            if ($guard !== null && ! $role->guard()->equals($guard)) {
                continue;
            }
            if ($tenantId !== null) {
                $roleTenant = $role->tenantId();
                if ($roleTenant === null || ! $roleTenant->equals($tenantId)) {
                    continue;
                }
            }
            $result[] = $role;
        }

        return $result;
    }

    public function save(Role $role): void
    {
        $this->byId[$role->id->value] = $role;
    }

    public function delete(Role $role): void
    {
        unset($this->byId[$role->id->value]);
    }

    public function findByName(string $name, GuardName $guard, ?Uuid $tenantId): ?Role
    {
        foreach ($this->byId as $role) {
            if ($role->name() !== $name) {
                continue;
            }
            if (! $role->guard()->equals($guard)) {
                continue;
            }
            $existingTenant = $role->tenantId();
            if ($tenantId === null && $existingTenant === null) {
                return $role;
            }
            if ($tenantId !== null && $existingTenant !== null && $existingTenant->equals($tenantId)) {
                return $role;
            }
        }

        return null;
    }

    public function searchPaginated(RoleFilter $filter, Pagination $pagination): PaginatedResult
    {
        $matches = [];
        foreach ($this->byId as $role) {
            if ($filter->guard !== null && ! $role->guard()->equals($filter->guard)) {
                continue;
            }
            if ($filter->tenantPresent) {
                $rt = $role->tenantId();
                if ($filter->tenantId === null) {
                    if ($rt !== null) {
                        continue;
                    }
                } else {
                    if ($rt === null || ! $rt->equals($filter->tenantId)) {
                        continue;
                    }
                }
            }
            if ($filter->isSystem !== null && $role->isSystem() !== $filter->isSystem) {
                continue;
            }
            if ($filter->levelMin !== null && $role->level()->value < $filter->levelMin) {
                continue;
            }
            if ($filter->levelMax !== null && $role->level()->value > $filter->levelMax) {
                continue;
            }
            if ($filter->hasParent !== null) {
                $has = $role->parentRoleId() !== null;
                if ($has !== $filter->hasParent) {
                    continue;
                }
            }
            $matches[] = $role;
        }

        usort($matches, static function (Role $a, Role $b): int {
            return $b->level()->value <=> $a->level()->value
                ?: strcmp($a->name(), $b->name());
        });

        $window = array_slice($matches, $pagination->offset, $pagination->limit);

        return new PaginatedResult(array_values($window), count($matches), $pagination);
    }

    public function resolveAncestors(Uuid $roleId): array
    {
        $ancestors = [];
        $visited = [$roleId->value => true];
        $current = $this->byId[$roleId->value] ?? null;
        while ($current !== null) {
            $parentId = $current->parentRoleId();
            if ($parentId === null || isset($visited[$parentId->value])) {
                break;
            }
            $visited[$parentId->value] = true;
            $parent = $this->byId[$parentId->value] ?? null;
            if ($parent === null) {
                break;
            }
            $ancestors[] = $parentId;
            $current = $parent;
        }

        return $ancestors;
    }
}
