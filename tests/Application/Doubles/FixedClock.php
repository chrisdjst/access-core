<?php

declare(strict_types=1);

namespace Modularize\Access\Tests\Application\Doubles;

use DateTimeImmutable;
use Modularize\Access\Domain\Shared\Clock;

/**
 * Mutable Clock test double for application-layer tests. Identical
 * in shape to the unit-layer FixedClock; kept separate so test
 * helpers in each suite can evolve independently.
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
