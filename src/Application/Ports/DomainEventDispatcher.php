<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use ModularizeRbac\Core\Domain\Shared\DomainEvent;

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
