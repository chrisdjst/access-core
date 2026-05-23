<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Exceptions;

use DomainException;

/**
 * Raised when an authenticated principal lacks the permission to
 * execute a use-case. Adapters at the HTTP boundary translate this
 * to 403 responses.
 */
final class AuthorizationFailed extends DomainException
{
    public function __construct(public readonly string $ability)
    {
        parent::__construct(sprintf('Authorization failed: %s', $ability));
    }

    public static function of(string $ability): self
    {
        return new self($ability);
    }
}
