<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Ports;

use Modularize\Access\Domain\Shared\Uuid;
use Modularize\Access\Exceptions\AuthorizationFailed;

/**
 * Port for checking whether the current actor may execute an ability
 * (e.g. `admin.modules.view`). Use-cases call `ensure()` at the top
 * of their body; the bridge adapter delegates to whatever the host
 * uses (Laravel's `Gate`, Symfony's `Security`, a CLI bypass, etc.).
 *
 * The optional `actorId` is the current authenticated user's id when
 * the host can supply one — handy for use-cases that record
 * `updated_by` audit columns. May be null when running unauthenticated
 * (CLI, scheduled jobs).
 */
interface Authorizer
{
    public function actorId(): ?Uuid;

    public function can(string $ability): bool;

    /**
     * @throws AuthorizationFailed when the actor lacks the ability.
     */
    public function ensure(string $ability): void;
}
