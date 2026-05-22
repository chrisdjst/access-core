<?php

declare(strict_types=1);

namespace Modularize\Access\Tests\Unit\TestDoubles;

use DateTimeImmutable;
use Modularize\Access\Domain\Shared\Clock;

/**
 * Trivial Clock test double. The application-layer in-memory adapter
 * coming in PR 2 will provide a more elaborate version (tickable,
 * mutable). For Unit tests of domain code we only need a frozen
 * timestamp.
 */
final class FixedClock implements Clock
{
    public function __construct(private DateTimeImmutable $now)
    {
    }

    public static function at(string $iso8601): self
    {
        return new self(new DateTimeImmutable($iso8601));
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function tick(string $interval): void
    {
        $this->now = $this->now->modify($interval);
    }
}
