<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Tests\Application\Doubles;

use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\AuthorizationFailed;

/**
 * Authorizer test double with a configurable allow/deny set and an
 * optional actor id. By default every ability is allowed and there
 * is no actor — call `deny()` to flip behavior for failure-path tests.
 */
final class AllowingAuthorizer implements Authorizer
{
    /** @var array<string, bool> */
    private array $abilities = [];

    private bool $defaultAllow = true;

    public function __construct(private ?Uuid $actorId = null)
    {
    }

    public function actAs(?Uuid $actorId): void
    {
        $this->actorId = $actorId;
    }

    public function denyByDefault(): void
    {
        $this->defaultAllow = false;
    }

    public function allow(string $ability): void
    {
        $this->abilities[$ability] = true;
    }

    public function deny(string $ability): void
    {
        $this->abilities[$ability] = false;
    }

    public function actorId(): ?Uuid
    {
        return $this->actorId;
    }

    public function can(string $ability): bool
    {
        return $this->abilities[$ability] ?? $this->defaultAllow;
    }

    public function ensure(string $ability): void
    {
        if (! $this->can($ability)) {
            throw AuthorizationFailed::of($ability);
        }
    }
}
