<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Port that maps a host User identifier to the set of role ids the
 * user holds. The core deliberately doesn't model a `User` aggregate —
 * the host owns that concept (Laravel: `App\Models\User`). This port
 * is the boundary the core uses to ask "what roles does this caller
 * have" without taking a dependency on the User type.
 *
 * Adapters (e.g. the Laravel bridge in PR V2.4) implement this by
 * reading from the `role_user` pivot.
 */
interface UserRoleResolver
{
    /**
     * @return list<Uuid>
     */
    public function roleIdsFor(Uuid $userId): array;
}
