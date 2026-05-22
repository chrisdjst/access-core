<?php

declare(strict_types=1);

namespace Modularize\Access\Domain\RoleModulePermission;

use Modularize\Access\Domain\Permission\PermissionName;

/**
 * Output of {@see RoleModulePermissionSynchronizer::diff()}: the two
 * disjoint lists of permission names the application layer must
 * apply to bring a role's effective permissions in line with the
 * desired flag set for a module.
 *
 * `isNoop()` lets callers skip persisting an event when nothing
 * actually changed — avoids spamming subscribers (e.g. the Spatie
 * adapter) for idempotent re-syncs.
 */
final readonly class RoleModuleSyncPlan
{
    /**
     * @param  list<PermissionName>  $toGrant
     * @param  list<PermissionName>  $toRevoke
     */
    public function __construct(
        public array $toGrant,
        public array $toRevoke,
    ) {
    }

    public function isNoop(): bool
    {
        return $this->toGrant === [] && $this->toRevoke === [];
    }
}
