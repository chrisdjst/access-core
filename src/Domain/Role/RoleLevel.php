<?php

declare(strict_types=1);

namespace Modularize\Access\Domain\Role;

use Modularize\Access\Exceptions\InvalidInput;

/**
 * Non-negative integer encoding role precedence. Higher levels are
 * "more privileged" — the host app decides what the absolute scale
 * means (e.g. 0 = guest, 100 = super-admin). The domain only enforces
 * that levels are comparable non-negative integers.
 */
final readonly class RoleLevel
{
    public int $value;

    public function __construct(int $value)
    {
        if ($value < 0) {
            throw InvalidInput::of('role_level', "Role level must be >= 0. Got: {$value}");
        }
        $this->value = $value;
    }

    public function isHigherThan(self $other): bool
    {
        return $this->value > $other->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
