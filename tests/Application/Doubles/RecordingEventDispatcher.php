<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Tests\Application\Doubles;

use ModularizeRbac\Core\Application\Ports\DomainEventDispatcher;
use ModularizeRbac\Core\Domain\Shared\DomainEvent;

/**
 * Event dispatcher that records everything it receives so tests can
 * assert on the emitted sequence without coupling to a real bus.
 */
final class RecordingEventDispatcher implements DomainEventDispatcher
{
    /** @var list<DomainEvent> */
    public array $dispatched = [];

    public function dispatch(DomainEvent $event): void
    {
        $this->dispatched[] = $event;
    }

    public function dispatchAll(iterable $events): void
    {
        foreach ($events as $event) {
            $this->dispatched[] = $event;
        }
    }
}
