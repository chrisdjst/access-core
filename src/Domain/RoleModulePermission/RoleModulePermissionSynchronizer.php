<?php

declare(strict_types=1);

namespace Modularize\Access\Domain\RoleModulePermission;

use Modularize\Access\Domain\Module\ModulePermission;
use Modularize\Access\Domain\Module\ModuleSlug;
use Modularize\Access\Domain\Permission\PermissionName;

/**
 * Pure-function domain service that computes the diff between a
 * role's *currently* held permissions for a module and the permissions
 * it *should* hold according to the new flag set.
 *
 * Extracted from the legacy `RoleModulePermissionObserver::sync()` —
 * but stripped of all I/O. The application layer is responsible for
 * obtaining the current permission list (via the role repository or
 * an external gateway like Spatie) and for applying the resulting
 * grant/revoke plan in a unit of work.
 *
 * Crucial invariant: only the five canonical actions
 * (list/view/create/update/delete) are scoped. Permission names that
 * follow the same `{slug}.X` prefix but encode extras
 * (manage/sign/approve/import/export, etc.) MUST NOT be revoked here —
 * they're managed elsewhere.
 */
final class RoleModulePermissionSynchronizer
{
    public function __construct(
        private readonly PermissionFlagResolver $flagResolver = new PermissionFlagResolver(),
    ) {
    }

    /**
     * Compute the diff between the role's current permissions for a
     * module and the desired permissions implied by the flag set.
     *
     * @param  list<PermissionName>  $currentPermissions  full set of permissions held by the role
     */
    public function diff(
        ModuleSlug $slug,
        ModulePermission $desired,
        array $currentPermissions,
    ): RoleModuleSyncPlan {
        $managedActions = $this->flagResolver->managedActions();
        $managedNames = array_map(
            static fn (string $action) => PermissionName::fromParts($slug, $action)->value,
            $managedActions,
        );

        $currentManaged = [];
        foreach ($currentPermissions as $name) {
            if ($name->moduleSlug->equals($slug) && in_array($name->action, $managedActions, true)) {
                $currentManaged[$name->value] = $name;
            }
        }

        $desiredNames = [];
        foreach ($this->flagResolver->permissionNamesFor($desired, $slug) as $name) {
            $desiredNames[$name->value] = $name;
        }

        $toRevoke = [];
        foreach ($currentManaged as $key => $name) {
            if (! isset($desiredNames[$key])) {
                $toRevoke[] = $name;
            }
        }

        $toGrant = [];
        foreach ($desiredNames as $key => $name) {
            if (! isset($currentManaged[$key])) {
                $toGrant[] = $name;
            }
        }

        unset($managedNames); // sanity: variable kept only for clarity in the code above

        return new RoleModuleSyncPlan(toGrant: $toGrant, toRevoke: $toRevoke);
    }

    /**
     * Compute the permissions to revoke when a {@see RoleModulePermission}
     * binding is removed entirely: every permission the role holds
     * scoped to the module's slug, regardless of action — the binding
     * no longer justifies any of them.
     *
     * Mirrors the legacy `deleted()` observer hook.
     *
     * @param  list<PermissionName>  $currentPermissions
     * @return list<PermissionName>
     */
    public function permissionsToRevokeOnUnbind(
        ModuleSlug $slug,
        array $currentPermissions,
    ): array {
        $toRevoke = [];
        foreach ($currentPermissions as $name) {
            if ($name->moduleSlug->equals($slug)) {
                $toRevoke[] = $name;
            }
        }

        return $toRevoke;
    }
}
