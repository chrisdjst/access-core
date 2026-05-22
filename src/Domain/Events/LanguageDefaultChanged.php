<?php

declare(strict_types=1);

namespace Modularize\Access\Domain\Events;

use DateTimeImmutable;
use Modularize\Access\Domain\Shared\DomainEvent;
use Modularize\Access\Domain\Shared\Uuid;

/**
 * Emitted when the system default language changes. Carries the
 * previous default id (if any) so subscribers can react to both ends
 * of the swap.
 */
final readonly class LanguageDefaultChanged implements DomainEvent
{
    public function __construct(
        public ?Uuid $previousDefaultId,
        public Uuid $newDefaultId,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
