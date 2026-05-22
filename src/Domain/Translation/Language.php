<?php

declare(strict_types=1);

namespace Modularize\Access\Domain\Translation;

use DateTimeImmutable;
use Modularize\Access\Domain\Shared\Clock;
use Modularize\Access\Domain\Shared\RecordsEvents;
use Modularize\Access\Domain\Shared\Uuid;

/**
 * A language available for translating modules and roles. Exactly one
 * Language in the system is marked `isDefault`; the default acts as
 * the fallback when a translation is missing for a requested locale.
 *
 * Switching the default language is a domain operation: the
 * application layer is responsible for ensuring exactly one default
 * exists at any time (typically by demoting the previous default in
 * the same unit of work).
 */
final class Language
{
    use RecordsEvents;

    public function __construct(
        public readonly Uuid $id,
        private LanguageCode $code,
        private string $name,
        private bool $isDefault,
        private bool $isActive,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        Uuid $id,
        LanguageCode $code,
        string $name,
        bool $isDefault,
        bool $isActive,
        Clock $clock,
    ): self {
        $now = $clock->now();

        return new self(
            id: $id,
            code: $code,
            name: $name,
            isDefault: $isDefault,
            isActive: $isActive,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function code(): LanguageCode
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function rename(string $name, Clock $clock): void
    {
        if ($name === $this->name) {
            return;
        }
        $this->name = $name;
        $this->updatedAt = $clock->now();
    }

    public function activate(Clock $clock): void
    {
        if ($this->isActive) {
            return;
        }
        $this->isActive = true;
        $this->updatedAt = $clock->now();
    }

    public function deactivate(Clock $clock): void
    {
        if (! $this->isActive) {
            return;
        }
        $this->isActive = false;
        $this->updatedAt = $clock->now();
    }

    /**
     * Mark this language as the system default. Demotion of the
     * previous default must be coordinated by the use-case so both
     * changes commit atomically.
     */
    public function markAsDefault(Clock $clock): void
    {
        if ($this->isDefault) {
            return;
        }
        $this->isDefault = true;
        $this->updatedAt = $clock->now();
    }

    public function demoteFromDefault(Clock $clock): void
    {
        if (! $this->isDefault) {
            return;
        }
        $this->isDefault = false;
        $this->updatedAt = $clock->now();
    }
}
