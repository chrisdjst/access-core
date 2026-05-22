<?php

declare(strict_types=1);

namespace Modularize\Access\Tests\Application\Doubles;

use Modularize\Access\Application\Ports\RoleRepository;
use Modularize\Access\Domain\Role\GuardName;
use Modularize\Access\Domain\Role\Role;
use Modularize\Access\Domain\Shared\Uuid;

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
}
