<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Audit;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Audit\AuditEntry;

final readonly class AuditEntryOutput
{
    public function __construct(
        public string $id,
        public string $event,
        public ?string $actorId,
        public ?string $tenantId,
        /** @var array<string, mixed> */
        public array $payload,
        public DateTimeImmutable $occurredAt,
    ) {
    }

    public static function fromEntity(AuditEntry $entry): self
    {
        return new self(
            id: $entry->id->value,
            event: $entry->event->value,
            actorId: $entry->actorId?->value,
            tenantId: $entry->tenantId?->value,
            payload: $entry->payload,
            occurredAt: $entry->occurredAt,
        );
    }
}
