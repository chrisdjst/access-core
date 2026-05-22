<?php

declare(strict_types=1);

namespace Modularize\Access\Domain\Events;

use DateTimeImmutable;
use Modularize\Access\Domain\Module\ModuleSlug;
use Modularize\Access\Domain\Shared\DomainEvent;
use Modularize\Access\Domain\Shared\Uuid;

final readonly class ModuleDeleted implements DomainEvent
{
    public function __construct(
        public Uuid $moduleId,
        public ModuleSlug $slug,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
