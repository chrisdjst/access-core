<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Module;

use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Release channel for a module version.
 * Valid promotion lattice: alpha → beta → stable.
 * Skipping channels and demoting are both prohibited.
 */
enum Channel: string
{
    case Alpha = 'alpha';
    case Beta = 'beta';
    case Stable = 'stable';

    public function canPromoteTo(self $target): bool
    {
        return match ($this) {
            self::Alpha => $target === self::Beta,
            self::Beta => $target === self::Stable,
            self::Stable => false,
        };
    }

    public function assertCanPromoteTo(self $target): void
    {
        if (! $this->canPromoteTo($target)) {
            throw InvalidInput::of(
                'channel',
                "Cannot promote from '{$this->value}' to '{$target->value}'. "
                . 'Valid lattice: alpha → beta → stable.'
            );
        }
    }
}
