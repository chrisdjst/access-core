<?php

declare(strict_types=1);

namespace Modularize\Access\Tests\Application\Doubles;

use Modularize\Access\Application\Ports\UnitOfWork;

/**
 * UnitOfWork double that runs the closure immediately, no transaction
 * semantics. Sufficient for application-level testing where we don't
 * care about rollback — that lives in the bridge adapter's
 * integration tests.
 */
final class PassthroughUnitOfWork implements UnitOfWork
{
    public function transactional(callable $work): mixed
    {
        return $work();
    }
}
