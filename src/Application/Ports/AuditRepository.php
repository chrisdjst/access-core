<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use ModularizeRbac\Core\Application\Audit\AuditQuery;
use ModularizeRbac\Core\Domain\Audit\AuditEntry;

/**
 * Persistence port for {@see AuditEntry} rows.
 *
 * Implementations MUST be append-only — audit entries are never
 * updated or deleted through this port. Retention/archival policies
 * live outside the application layer (e.g. a separate maintenance
 * command or DB-level partition rotation).
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
}
