<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Module;

use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * A module's stable, URL-safe identifier. Used to build Spatie-style
 * permission names of the form `{slug}.{action}` (e.g. "events.view",
 * "billing.invoices.create").
 *
 * Allowed: lowercase ASCII letters, digits, and dots. Dots are used to
 * express sub-module hierarchy in the permission name space.
 * Slugs may not start or end with a dot, nor contain consecutive dots.
 */
final readonly class ModuleSlug
{
    private const PATTERN = '/^[a-z0-9]+(\.[a-z0-9]+)*$/';

    public string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw InvalidInput::of('module_slug', 'Module slug cannot be empty.');
        }
        if (preg_match(self::PATTERN, $trimmed) !== 1) {
            throw InvalidInput::of(
                'module_slug',
                "Module slug must match /[a-z0-9]+(.[a-z0-9]+)*/. Got: {$value}"
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
