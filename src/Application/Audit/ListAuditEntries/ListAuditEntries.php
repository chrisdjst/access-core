<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Audit\ListAuditEntries;

use ModularizeRbac\Core\Application\Audit\AuditEntryOutput;
use ModularizeRbac\Core\Application\Ports\AuditRepository;
use ModularizeRbac\Core\Application\Ports\Authorizer;

/**
 * Use-case: paginate the audit log applying caller-supplied filters.
 *
 * Authorization: `admin.audit.view`.
 *
 * The use-case does not enforce tenant scoping by itself; callers
 * that want to limit visibility to the current tenant pass the
 * `tenantId` filter explicitly (typically derived from
 * {@see \ModularizeRbac\Core\Application\Ports\TenantContext} at
 * the HTTP boundary).
 */
final class ListAuditEntries
{
    public function __construct(
        private readonly AuditRepository $entries,
        private readonly Authorizer $authorizer,
    ) {
    }

    public function execute(ListAuditEntriesInput $input): ListAuditEntriesOutput
    {
        $this->authorizer->ensure('admin.audit.view');

        $rows = $this->entries->search($input->query);
        $total = $this->entries->count($input->query);

        $output = [];
        foreach ($rows as $entry) {
            $output[] = AuditEntryOutput::fromEntity($entry);
        }

        return new ListAuditEntriesOutput(
            entries: $output,
            total: $total,
            limit: $input->query->limit,
            offset: $input->query->offset,
        );
    }
}
