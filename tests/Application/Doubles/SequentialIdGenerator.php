<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Tests\Application\Doubles;

use ModularizeRbac\Core\Domain\Shared\IdGenerator;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Deterministic UUID-like id generator for tests. Emits a sequence of
 * predictable canonical UUIDs (e.g. 00000000-0000-0000-0000-000000000001)
 * so tests can assert against exact ids without rerolling on every run.
 */
final class SequentialIdGenerator implements IdGenerator
{
    private int $counter = 0;

    public function nextUuid(): Uuid
    {
        $this->counter++;
        $hex = str_pad(dechex($this->counter), 12, '0', STR_PAD_LEFT);

        return new Uuid('00000000-0000-0000-0000-'.$hex);
    }
}
