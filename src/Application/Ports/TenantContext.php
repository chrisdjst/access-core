<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Port for the currently active tenant in a multi-tenant host.
 *
 * Use-cases consult this when their behavior depends on the caller's
 * tenant — e.g. defaulting `tenant_id` filters on listings, scoping
 * audit log lookups, or resolving the owning tenant for a newly
 * created Role when the input doesn't specify one.
 *
 * Single-tenant hosts can return null unconditionally; their use-cases
 * won't reach for the value because `Role::isGlobal()` already covers
 * that case. Multi-tenant hosts implement this against their request
 * lifecycle (Laravel adapter reads from a container binding seeded by
 * the host's tenant-resolution middleware).
 */
interface TenantContext
{
    public function currentTenantId(): ?Uuid;
}
