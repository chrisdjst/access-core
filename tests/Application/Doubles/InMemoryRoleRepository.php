<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Tests\Application\Doubles;

use ModularizeRbac\Core\Application\Ports\RoleRepository;
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
}
