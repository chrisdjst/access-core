<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Audit;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Immutable record of a single auditable event. The payload is a free
 * key/value map carrying whatever identifiers + diff data the
 * subscriber chose to serialize — keep it small, the audit log is
 * meant for forensics and admin UI, not for replaying business state.
 *
 * `actorId` may be null (CLI / system jobs); `tenantId` may be null
 * (single-tenant hosts or events that aren't tenant-scoped). The
 * Laravel `AuditingListener` (PR V2.5) populates both from the
 * Authorizer + TenantContext ports.
 */
final class AuditEntry
{
    public function __construct(
        public readonly Uuid $id,
        public readonly AuditEventName $event,
        public readonly ?Uuid $actorId,
        public readonly ?Uuid $tenantId,
        /** @var array<string, mixed> */
        public readonly array $payload,
        public readonly DateTimeImmutable $occurredAt,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function record(
        Uuid $id,
        AuditEventName $event,
        ?Uuid $actorId,
        ?Uuid $tenantId,
        array $payload,
        Clock $clock,
    ): self {
        return new self(
            id: $id,
            event: $event,
            actorId: $actorId,
            tenantId: $tenantId,
            payload: $payload,
            occurredAt: $clock->now(),
        );
    }
}
