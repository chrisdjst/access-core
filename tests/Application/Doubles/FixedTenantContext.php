<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Tests\Application\Doubles;

use ModularizeRbac\Core\Application\Ports\TenantContext;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Tenant context test double. Defaults to single-tenant (null);
 * call `withTenant()` to pin a specific id for a test case.
 */
final class FixedTenantContext implements TenantContext
{
    public function __construct(private ?Uuid $current = null)
    {
    }

    public function withTenant(?Uuid $id): void
    {
        $this->current = $id;
    }

    public function currentTenantId(): ?Uuid
    {
        return $this->current;
    }
}
