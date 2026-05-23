<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Shared;

use DateTimeImmutable;

/**
 * Port for reading "now". Inject this everywhere the domain needs a
 * timestamp so tests can pin the clock and reason about ordering
 * without sleeping or stubbing globals.
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}
