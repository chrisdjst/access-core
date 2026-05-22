<?php

declare(strict_types=1);

namespace Modularize\Access\Exceptions;

use DomainException;

/**
 * Raised by repositories and use-cases when a referenced aggregate
 * does not exist. Adapters at the HTTP boundary translate this to
 * 404 responses.
 */
final class NotFound extends DomainException
{
    public function __construct(public readonly string $aggregate, public readonly string $id)
    {
        parent::__construct(sprintf('%s not found: %s', $aggregate, $id));
    }

    public static function of(string $aggregate, string $id): self
    {
        return new self($aggregate, $id);
    }
}
