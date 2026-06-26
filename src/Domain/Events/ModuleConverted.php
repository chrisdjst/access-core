<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Events;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Shared\DomainEvent;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Fired when a subject completes a meaningful action within a module version
 * (e.g. "feature_used", "upgrade_clicked"). The host application defines
 * `conversionKind` — the library does not constrain it beyond being a
 * non-empty string. This event is never sampled: every conversion is recorded.
 *
 * AuditingListener derives event name 'module.converted' from this class name.
 */
final readonly class ModuleConverted implements DomainEvent
{
    public function __construct(
        public Uuid $moduleId,
        public Uuid $versionId,
        public Uuid $subjectId,
        public string $conversionKind,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
