<?php

declare(strict_types=1);

namespace Modularize\Access\Exceptions;

use DomainException;

/**
 * Raised when a value object constructor or use-case input rejects a
 * value as malformed or violating an invariant. The `field` identifies
 * which input was bad — useful for surfacing 422 responses or CLI
 * error messages at the boundary.
 */
final class InvalidInput extends DomainException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }

    public static function of(string $field, string $message): self
    {
        return new self($field, $message);
    }
}
