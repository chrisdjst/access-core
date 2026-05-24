<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Write-side counterpart to {@see UserRoleResolver}. The domain has no
 * `User` aggregate — users live in the host application — so the
 * write path against the `role_user` pivot is exposed as a thin port
 * that the host implements with whatever storage it uses.
 *
 * Implementations are expected to be idempotent: re-assigning the
 * same (roleId, userId, tenantId) tuple is a no-op rather than an
 * error. The host is responsible for the underlying uniqueness
 * constraint that supports this contract.
 */
interface UserRoleAssigner
{
    /**
     * Bind a user to a role, optionally scoped to a tenant. Idempotent:
     * an existing binding is left untouched.
     */
    public function assign(Uuid $roleId, Uuid $userId, ?Uuid $tenantId = null): void;
}
