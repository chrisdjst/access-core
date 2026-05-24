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

    /**
     * Delete a role from persistence. The caller is responsible for
     * making sure no live bindings remain (DeleteRole use-case
     * enforces that invariant). Adapters that have FK constraints
     * should rely on them as a defence in depth.
     */
    public function delete(Role $role): void;

    /**
     * Find a role by its (name, guard, tenantId) tuple. Used by the
     * CreateRole use-case to enforce uniqueness — the database has a
     * unique constraint covering these columns, but failing fast in
     * the application layer surfaces a friendlier error.
     */
    public function findByName(string $name, GuardName $guard, ?Uuid $tenantId): ?Role;

    /**
     * Walk the role's `parentRoleId` chain and return every ancestor's
     * id in order (immediate parent first, root last). An orphaned
     * pointer (parent_role_id references a deleted row) ends the walk
     * silently. The role itself is NOT included.
     *
     * Implementations must guard against cycles: a malformed chain
     * (a → b → a) returns the ids walked so far and stops. The
     * domain creator (`Role::create`) prevents self-parenting; cycles
     * across longer chains can only arise from direct SQL edits.
     *
     * @return list<Uuid>
     */
    public function resolveAncestors(Uuid $roleId): array;
}
