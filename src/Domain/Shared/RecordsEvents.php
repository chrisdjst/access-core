<?php

declare(strict_types=1);

namespace Modularize\Access\Domain\Shared;

/**
 * Trait for aggregates that record domain events. The application
 * layer pulls events via {@see pullDomainEvents()} after a successful
 * unit of work and hands them to the dispatcher.
 */
trait RecordsEvents
{
    /** @var list<DomainEvent> */
    private array $pendingEvents = [];

    protected function recordEvent(DomainEvent $event): void
    {
        $this->pendingEvents[] = $event;
    }

    /**
     * @return list<DomainEvent>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->pendingEvents;
        $this->pendingEvents = [];

        return $events;
    }
}
