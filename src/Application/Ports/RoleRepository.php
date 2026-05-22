<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Ports;

use Modularize\Access\Domain\Role\GuardName;
use Modularize\Access\Domain\Role\Role;
use Modularize\Access\Domain\Shared\Uuid;

interface RoleRepository
{
    public function find(Uuid $id): ?Role;

    /**
     * List roles optionally filtered by guard and tenant. A null
     * `tenantId` matches *only* global (tenant-less) roles; pass
     * `null` for that filter to skip tenant scoping entirely.
     *
     * @return list<Role>
     */
    public function search(?GuardName $guard, ?Uuid $tenantId): array;

    public function save(Role $role): void;
}
