<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Audit\ListAuditEntries;

use DateTimeImmutable;
use Exception;
use ModularizeRbac\Core\Application\Audit\AuditQuery;
use ModularizeRbac\Core\Domain\Audit\AuditEventName;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * HTTP/CLI-friendly input shape for {@see ListAuditEntries}. Every
 * field arrives as a raw string (or null) and is normalized into the
 * appropriate value object before being wrapped in {@see AuditQuery}.
 */
final readonly class ListAuditEntriesInput
{
    public AuditQuery $query;

    public function __construct(
        ?string $event = null,
        ?string $actorId = null,
        ?string $tenantId = null,
        ?string $since = null,
        ?string $until = null,
        int $limit = 100,
        int $offset = 0,
    ) {
        $this->query = new AuditQuery(
            event: $event !== null ? new AuditEventName($event) : null,
            actorId: $actorId !== null ? new Uuid($actorId) : null,
            tenantId: $tenantId !== null ? new Uuid($tenantId) : null,
            since: self::parseDate($since, 'since'),
            until: self::parseDate($until, 'until'),
            limit: $limit,
            offset: $offset,
        );
    }

    private static function parseDate(?string $raw, string $field): ?DateTimeImmutable
    {
        if ($raw === null) {
            return null;
        }
        try {
            return new DateTimeImmutable($raw);
        } catch (Exception) {
            throw InvalidInput::of($field, "Could not parse date: {$raw}");
        }
    }
}
