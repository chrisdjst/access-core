<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Module;

use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Deterministic 0–99 bucket assignment for A/B cohort splitting.
 *
 * The bucket is derived from a stable hash so the same subject always
 * lands in the same bucket, regardless of bucket_pct changes. A
 * configurable salt prevents cross-module correlation and lets operators
 * rotate assignments without changing module or user IDs.
 *
 * Bucket assignment: SHA-256(salt|moduleId|subjectId) → first 8 hex chars → uint32 % 100.
 */
final readonly class CohortBucket
{
    private function __construct(private int $value)
    {
    }

    public static function forSubject(Uuid $moduleId, Uuid $subjectId, string $salt): self
    {
        $raw = hash('sha256', $salt . '|' . $moduleId->value . '|' . $subjectId->value);
        $bucket = (int) (hexdec(substr($raw, 0, 8)) % 100);

        return new self($bucket);
    }

    public function lessThan(int $pct): bool
    {
        return $this->value < $pct;
    }

    public function value(): int
    {
        return $this->value;
    }
}
