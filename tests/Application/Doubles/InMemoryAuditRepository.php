<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Tests\Application\Doubles;

use DateTimeImmutable;
use ModularizeRbac\Core\Application\Audit\AuditQuery;
use ModularizeRbac\Core\Application\Ports\AuditRepository;
use ModularizeRbac\Core\Domain\Audit\AuditEntry;

/**
 * In-memory implementation. Search applies filters mechanically
 * then sorts most-recent-first before paging. The deleteOlderThan
 * primitive supports retention tests.
 */
final class InMemoryAuditRepository implements AuditRepository
{
    /** @var list<AuditEntry> */
    private array $entries = [];

    public function save(AuditEntry $entry): void
    {
        $this->entries[] = $entry;
    }

    public function search(AuditQuery $query): array
    {
        $filtered = $this->applyFilters($query);
        usort(
            $filtered,
            static fn (AuditEntry $a, AuditEntry $b) => $b->occurredAt <=> $a->occurredAt
        );

        return array_slice($filtered, $query->offset, $query->limit);
    }

    public function count(AuditQuery $query): int
    {
        return count($this->applyFilters($query));
    }

    public function deleteOlderThan(DateTimeImmutable $cutoff): int
    {
        $kept = [];
        $removed = 0;
        foreach ($this->entries as $entry) {
            if ($entry->occurredAt < $cutoff) {
                $removed++;
                continue;
            }
            $kept[] = $entry;
        }
        $this->entries = $kept;

        return $removed;
    }

    /**
     * @return list<AuditEntry>
     */
    private function applyFilters(AuditQuery $query): array
    {
        $rows = [];
        foreach ($this->entries as $entry) {
            if ($query->event !== null && ! $entry->event->equals($query->event)) {
                continue;
            }
            if ($query->actorId !== null) {
                if ($entry->actorId === null || ! $entry->actorId->equals($query->actorId)) {
                    continue;
                }
            }
            if ($query->tenantId !== null) {
                if ($entry->tenantId === null || ! $entry->tenantId->equals($query->tenantId)) {
                    continue;
                }
            }
            if ($query->since !== null && $entry->occurredAt < $query->since) {
                continue;
            }
            if ($query->until !== null && $entry->occurredAt > $query->until) {
                continue;
            }
            $rows[] = $entry;
        }

        return $rows;
    }
}
