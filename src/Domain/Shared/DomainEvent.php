<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Shared;

use DateTimeImmutable;

/**
 * Marker for events recorded by aggregates and pulled by the
 * application layer at commit time. Events are immutable value
 * objects — concrete implementations should be `final readonly` and
 * carry only the data needed by subscribers.
 */
interface DomainEvent
{
    public function occurredAt(): DateTimeImmutable;
}
