<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Audit;

use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Dotted snake_case identifier for an audited event, e.g. `module.created`,
 * `role.permissions_changed`, `language.default_changed`.
 *
 * The shape mirrors the concrete `DomainEvent` class names rendered as
 * `{aggregate}.{action}` — the audit listener in the Laravel bridge
 * derives names from the event class, but the domain accepts whatever
 * the host emits as long as it matches the pattern.
 */
final readonly class AuditEventName
{
    private const PATTERN = '/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/';

    public string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw InvalidInput::of('audit_event_name', 'Audit event name cannot be empty.');
        }
        if (preg_match(self::PATTERN, $trimmed) !== 1) {
            throw InvalidInput::of(
                'audit_event_name',
                "Audit event name must be dotted snake_case (e.g. 'module.created'). Got: {$value}"
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
