<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use DateTimeImmutable;
use ModularizeRbac\Core\Application\Audit\AuditQuery;
use ModularizeRbac\Core\Domain\Audit\AuditEntry;

/**
 * Persistence port for {@see AuditEntry} rows.
 *
 * Writes through this port are append-only — entries are never
 * updated. The single exception is bulk removal of older entries
 * via {@see deleteOlderThan()}, which exists to support retention
 * policies driven by an operator (typically the `access:audit:purge`
 * console command). Hosts that need finer-grained archival should
 * still use DB-level partition rotation outside this port.
 */
interface AuditRepository
{
    public function save(AuditEntry $entry): void;

    /**
     * @return list<AuditEntry>
     */
    public function search(AuditQuery $query): array;

    /**
     * Total count matching the same filters (ignoring limit/offset).
     * Used by callers that want to render a paginator.
     */
    public function count(AuditQuery $query): int;

    /**
     * Delete every entry whose `occurred_at < $cutoff`. Returns the
     * number of rows removed.
     *
     * Adapters should implement this as a single DELETE statement
     * with a `WHERE occurred_at < ?` clause — the table has an index
     * on `occurred_at` for that purpose.
     */
    public function deleteOlderThan(DateTimeImmutable $cutoff): int;
}
