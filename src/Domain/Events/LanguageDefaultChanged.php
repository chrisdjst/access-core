<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Events;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Shared\DomainEvent;
use ModularizeRbac\Core\Domain\Shared\Uuid;

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
