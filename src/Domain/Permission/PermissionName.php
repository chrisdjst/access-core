<?php

declare(strict_types=1);

namespace Modularize\Access\Domain\Permission;

use Modularize\Access\Domain\Module\ModuleSlug;
use Modularize\Access\Exceptions\InvalidInput;

/**
 * Canonical permission identifier of the form `{module_slug}.{action}`
 * — e.g. "events.view", "billing.invoices.create".
 *
 * The `action` segment may not contain dots; if you need nested
 * action namespaces, encode that into the slug instead. The full name
 * is what gets stored in Spatie's `permissions.name` column at the
 * infrastructure boundary.
 */
final readonly class PermissionName
{
    private const ACTION_PATTERN = '/^[a-z][a-z0-9_]*$/';

    public string $value;
    public string $action;
    public ModuleSlug $moduleSlug;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw InvalidInput::of('permission_name', 'Permission name cannot be empty.');
        }

        $lastDot = strrpos($trimmed, '.');
        if ($lastDot === false || $lastDot === 0 || $lastDot === strlen($trimmed) - 1) {
            throw InvalidInput::of(
                'permission_name',
                "Permission name must be of the form 'module_slug.action'. Got: {$value}"
            );
        }

        $slugPart = substr($trimmed, 0, $lastDot);
        $actionPart = substr($trimmed, $lastDot + 1);

        if (preg_match(self::ACTION_PATTERN, $actionPart) !== 1) {
            throw InvalidInput::of(
                'permission_name',
                "Action segment must match /[a-z][a-z0-9_]*/. Got: {$actionPart}"
            );
        }

        $this->moduleSlug = new ModuleSlug($slugPart);
        $this->action = $actionPart;
        $this->value = $this->moduleSlug->value.'.'.$actionPart;
    }

    public static function fromParts(ModuleSlug $slug, string $action): self
    {
        return new self($slug->value.'.'.$action);
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
