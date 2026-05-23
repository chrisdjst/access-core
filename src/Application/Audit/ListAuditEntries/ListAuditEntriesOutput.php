<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Audit\ListAuditEntries;

use ModularizeRbac\Core\Application\Audit\AuditEntryOutput;

/**
 * Paginated bundle returned by {@see ListAuditEntries}. The HTTP
 * adapter typically envelopes this as `{ data: [...], meta: { total,
 * limit, offset } }` — we expose the shape framework-agnostically
 * here so CLI / GraphQL callers can do whatever they need.
 */
final readonly class ListAuditEntriesOutput
{
    public function __construct(
        /** @var list<AuditEntryOutput> */
        public array $entries,
        public int $total,
        public int $limit,
        public int $offset,
    ) {
    }
}
