<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Audit;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Audit\AuditEventName;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Filter + pagination spec for {@see \ModularizeRbac\Core\Application\Ports\AuditRepository::search()}.
 *
 * Every filter is optional. Time bounds are inclusive. Pagination
 * uses limit/offset because the audit log is typically queried by
 * humans paging through history rather than streamed.
 */
final readonly class AuditQuery
{
    public function __construct(
        public ?AuditEventName $event = null,
        public ?Uuid $actorId = null,
        public ?Uuid $tenantId = null,
        public ?DateTimeImmutable $since = null,
        public ?DateTimeImmutable $until = null,
        public int $limit = 100,
        public int $offset = 0,
    ) {
        if ($this->limit < 1 || $this->limit > 1000) {
            throw InvalidInput::of('limit', "Limit must be between 1 and 1000. Got: {$this->limit}");
        }
        if ($this->offset < 0) {
            throw InvalidInput::of('offset', "Offset must be >= 0. Got: {$this->offset}");
        }
        if ($this->since !== null && $this->until !== null && $this->since > $this->until) {
            throw InvalidInput::of('since', 'since must be <= until.');
        }
    }
}
