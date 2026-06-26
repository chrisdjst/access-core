<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Events;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Module\Channel;
use ModularizeRbac\Core\Domain\Shared\DomainEvent;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Fired when a subject (user or tenant) is assigned to a module version
 * for the first time (or on a sampled request, per config).
 * `resolutionSource` indicates which path resolved the version:
 *   - 'override'        → explicit pin from module_cohort_overrides
 *   - 'hash'            → deterministic bucket assignment
 *   - 'stable_fallback' → no beta candidate available; fell back to stable
 *
 * AuditingListener derives event name 'module.exposed' from this class name.
 */
final readonly class ModuleExposed implements DomainEvent
{
    public function __construct(
        public Uuid $moduleId,
        public Uuid $versionId,
        public string $subjectType,
        public Uuid $subjectId,
        public Channel $channel,
        public string $resolutionSource,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
