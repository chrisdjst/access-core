<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Ports;

use Modularize\Access\Domain\Shared\DomainEvent;

/**
 * Sink for domain events that aggregates accumulate during a unit of
 * work. Use-cases pull events from aggregates after the transaction
 * commits and pass them here. The bridge adapter forwards them to
 * the host framework's event bus (e.g. Laravel's `event()` helper).
 *
 * Implementations MUST be tolerant of "no subscribers" — dispatching
 * an event with no listeners is a noop, not an error.
 */
interface DomainEventDispatcher
{
    public function dispatch(DomainEvent $event): void;

    /**
     * @param  iterable<DomainEvent>  $events
     */
    public function dispatchAll(iterable $events): void;
}
