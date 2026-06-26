<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\UpsertCohortOverride;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;

final readonly class UpsertCohortOverrideInput
{
    public Uuid $moduleId;
    public Uuid $moduleVersionId;
    public string $subjectType;
    public ?Uuid $subjectId;
    public ?string $reason;
    public ?DateTimeImmutable $expiresAt;

    public function __construct(
        string $moduleId,
        string $moduleVersionId,
        string $subjectType,
        ?string $subjectId,
        ?string $reason = null,
        ?string $expiresAt = null,
    ) {
        if (! in_array($subjectType, ['user', 'tenant'], true)) {
            throw InvalidInput::of('subject_type', "subject_type must be 'user' or 'tenant'.");
        }

        $this->moduleId = new Uuid($moduleId);
        $this->moduleVersionId = new Uuid($moduleVersionId);
        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId !== null ? new Uuid($subjectId) : null;
        $this->reason = $reason;
        $this->expiresAt = $expiresAt !== null ? new DateTimeImmutable($expiresAt) : null;
    }
}
