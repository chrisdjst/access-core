<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use ModularizeRbac\Core\Domain\Role\GuardName;
use ModularizeRbac\Core\Domain\Role\Role;
use ModularizeRbac\Core\Domain\Shared\Uuid;

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
