<?php

declare(strict_types=1);

namespace Modularize\Access\Domain\RoleModulePermission;

use Modularize\Access\Domain\Module\ModulePermission;
use Modularize\Access\Domain\Module\ModuleSlug;
use Modularize\Access\Domain\Permission\PermissionName;

/**
 * Pure mapping between the boolean flag set carried by a
 * {@see ModulePermission} and the canonical Spatie-style permission
 * names that compose a role's effective ability set.
 *
 * This is the extraction of the legacy `ModulePermission::FLAG_TO_ACTION`
 * + `allowedActions()` logic. By living as a domain service rather
 * than a method on the entity, it can be reused outside the entity
 * (e.g. by the synchronizer below) without forcing the caller to
 * hydrate an aggregate just to read flags.
 */
final class PermissionFlagResolver
{
    /**
     * Return the actions allowed by the flag set, in canonical order.
     *
     * @return list<string>  e.g. ['view', 'create'] when read+write flags are on.
     */
    public function allowedActions(ModulePermission $permission): array
    {
        $actions = [];
        foreach ($permission->flags() as $flag => $enabled) {
            if ($enabled) {
                $actions[] = ModulePermission::FLAG_TO_ACTION[$flag];
            }
        }

        return $actions;
    }

    /**
     * Return the canonical list of action names this resolver manages.
     * Useful for callers that need to scope a query to "the 5 actions
     * we own" — without enumerating extras like manage/sign/approve
     * that may exist alongside in the host's permission table.
     *
     * @return list<string>
     */
    public function managedActions(): array
    {
        return array_values(ModulePermission::FLAG_TO_ACTION);
    }

    /**
     * Build the fully-qualified permission names ({slug}.{action})
     * that this flag set permits for the given module.
     *
     * @return list<PermissionName>
     */
    public function permissionNamesFor(ModulePermission $permission, ModuleSlug $slug): array
    {
        $names = [];
        foreach ($this->allowedActions($permission) as $action) {
            $names[] = PermissionName::fromParts($slug, $action);
        }

        return $names;
    }
}
