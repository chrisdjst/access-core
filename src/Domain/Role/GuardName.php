<?php

declare(strict_types=1);

namespace Modularize\Access\Domain\Role;

use Modularize\Access\Exceptions\InvalidInput;

/**
 * The name of an auth guard (e.g. "web", "admin", "api"). Used by
 * Spatie's permission tables to isolate role/permission namespaces
 * per guard. The domain itself does not know about Spatie — this is
 * just a typed string that the infrastructure layer maps to whatever
 * guard concept the host framework provides.
 */
final readonly class GuardName
{
    private const PATTERN = '/^[a-z][a-z0-9_-]{0,63}$/';

    public string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw InvalidInput::of('guard_name', 'Guard name cannot be empty.');
        }
        if (preg_match(self::PATTERN, $trimmed) !== 1) {
            throw InvalidInput::of(
                'guard_name',
                "Guard name must be 1-64 chars matching /[a-z][a-z0-9_-]*/. Got: {$value}"
            );
        }
        $this->value = $trimmed;
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
