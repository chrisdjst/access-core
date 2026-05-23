<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Translation;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * A translated value for a single field of a single entity in a
 * single language. The polymorphic `translatableType` / `translatableId`
 * pair identifies the owning aggregate (e.g. a Module or a Role); the
 * pairing of (owner, language, field) is unique by domain rule and
 * enforced by the repository on persistence.
 */
final class Translation
{
    public function __construct(
        public readonly Uuid $id,
        public readonly string $translatableType,
        public readonly Uuid $translatableId,
        public readonly Uuid $languageId,
        public readonly string $field,
        private string $value,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        Uuid $id,
        string $translatableType,
        Uuid $translatableId,
        Uuid $languageId,
        string $field,
        string $value,
        Clock $clock,
    ): self {
        $now = $clock->now();

        return new self(
            id: $id,
            translatableType: $translatableType,
            translatableId: $translatableId,
            languageId: $languageId,
            field: $field,
            value: $value,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function value(): string
    {
        return $this->value;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function changeValue(string $value, Clock $clock): void
    {
        if ($this->value === $value) {
            return;
        }
        $this->value = $value;
        $this->updatedAt = $clock->now();
    }
}
