<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use ModularizeRbac\Core\Domain\Permission\PermissionName;
use ModularizeRbac\Core\Domain\Role\GuardName;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Optional port for replicating role/permission state into an external
 * authorization system — concretely, `spatie/laravel-permission`'s
 * `role_has_permissions` table so that `$user->can('events.view')`
 * keeps working through Laravel's `Gate`.
 *
 * The application layer calls `applyPlan()` after a SyncRoleModules
 * use-case commits. Implementations MUST be idempotent: granting an
 * already-granted permission is a noop, revoking a non-existing one
 * is a noop.
 *
 * A null-object implementation (`NullExternalPermissionGateway`,
 * supplied by the bridge for hosts that don't use Spatie) makes
 * `applyPlan()` a noop entirely.
 */
interface ExternalPermissionGateway
{
    /**
     * Look up the permission names currently held by the role for a
     * given guard. Used by the synchronizer to compute the diff.
     *
     * @return list<PermissionName>
     */
    public function permissionsHeldBy(Uuid $roleId, GuardName $guard): array;

    /**
     * @param  list<PermissionName>  $granted
     * @param  list<PermissionName>  $revoked
     */
    public function applyPlan(Uuid $roleId, GuardName $guard, array $granted, array $revoked): void;
}
