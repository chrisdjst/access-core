<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use ModularizeRbac\Core\Application\Role\RoleFilter;
use ModularizeRbac\Core\Application\Shared\PaginatedResult;
use ModularizeRbac\Core\Application\Shared\Pagination;
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
     * Hard-delete a role from persistence. v2.8+ use-cases call
     * {@see self::softDelete()} instead; this method is preserved for
     * hosts that explicitly need to purge a row (ops migration,
     * GDPR right-to-erasure response). FK cascades + binding
     * invariants are the caller's responsibility.
     */
    public function delete(Role $role): void;

    /**
     * Mark the role as soft-deleted (its `deletedAt` is non-null).
     * Subsequent `find()` / `search()` calls must NOT return it
     * unless adapters opt into a `withTrashed()` flag (out of scope
     * for the v2.8 port).
     */
    public function softDelete(Role $role): void;

    /**
     * Clear the soft-delete marker. Idempotent — restoring a row
     * that wasn't soft-deleted is a no-op.
     */
    public function restore(Role $role): void;

    /**
     * Look up a role by id even when it is soft-deleted. Used by the
     * {@see \ModularizeRbac\Core\Application\Role\RestoreRole\RestoreRole}
     * use-case which must reach trashed rows.
     */
    public function findIncludingTrashed(Uuid $id): ?Role;

    /**
     * Find a role by its (name, guard, tenantId) tuple. Used by the
     * CreateRole use-case to enforce uniqueness — the database has a
     * unique constraint covering these columns, but failing fast in
     * the application layer surfaces a friendlier error.
     */
    public function findByName(string $name, GuardName $guard, ?Uuid $tenantId): ?Role;

    /**
     * Windowed search over roles with an optional filter set.
     *
     * Same contract as {@see ModuleRepository::searchPaginated()}: the
     * returned PaginatedResult carries the windowed slice + the total
     * count of rows that matched.
     *
     * @return PaginatedResult<Role>
     */
    public function searchPaginated(RoleFilter $filter, Pagination $pagination): PaginatedResult;

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
