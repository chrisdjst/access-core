<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use DateTimeImmutable;
use ModularizeRbac\Core\Application\Module\CohortOverrideData;
use ModularizeRbac\Core\Domain\Shared\Uuid;

interface CohortOverrideRepository
{
    public function find(Uuid $id): ?CohortOverrideData;

    /**
     * Find an active (non-expired) override for a specific subject.
     * Checks tenant-wide pins (subject_id=null) first, then subject-specific.
     */
    public function findActive(
        Uuid $moduleId,
        string $subjectType,
        Uuid $subjectId,
        DateTimeImmutable $now,
    ): ?CohortOverrideData;

    /**
     * @return list<CohortOverrideData>
     */
    public function allByModule(Uuid $moduleId): array;

    public function save(CohortOverrideData $data): void;

    public function delete(Uuid $id): void;

    /**
     * Remove all auto-pinned overrides for a module (reason='auto-pinned-on-exposure').
     */
    public function deleteAutoPinnedByModule(Uuid $moduleId): void;
}
