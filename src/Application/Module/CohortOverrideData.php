<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Immutable projection of a module_cohort_overrides row.
 */
final readonly class CohortOverrideData
{
    public function __construct(
        public Uuid $id,
        public Uuid $moduleId,
        public Uuid $moduleVersionId,
        public string $subjectType,
        public ?Uuid $subjectId,
        public ?string $reason,
        public ?Uuid $pinnedBy,
        public DateTimeImmutable $pinnedAt,
        public ?DateTimeImmutable $expiresAt,
    ) {
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $this->expiresAt !== null && $this->expiresAt <= $now;
    }

    public function isTenantWide(): bool
    {
        return $this->subjectId === null;
    }
}
