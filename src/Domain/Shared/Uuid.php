<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Shared;

use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Immutable UUID value object. Validates that the string matches the
 * RFC 4122 canonical 8-4-4-4-12 hex form (any version, any variant).
 *
 * Generation is delegated to the {@see IdGenerator} port — the domain
 * never reaches for `Str::uuid()` or `uuid_create()` directly.
 */
final readonly class Uuid
{
    private const PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    public string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower($value);
        if (preg_match(self::PATTERN, $normalized) !== 1) {
            throw InvalidInput::of('uuid', "Expected canonical UUID, got: {$value}");
        }
        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
