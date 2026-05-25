<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\RoleModulePermission;

use ModularizeRbac\Core\Domain\Module\ModulePermission;
use ModularizeRbac\Core\Domain\Module\ModuleSlug;
use ModularizeRbac\Core\Domain\Permission\PermissionName;

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
    public function __construct(
        private readonly PermissionActionRegistry $registry = new PermissionActionRegistry(),
    ) {
    }

    /**
     * Return the actions allowed by the flag set, in canonical order.
     * Includes both the 5 native flags AND any custom flag registered
     * via {@see PermissionActionRegistry::register()}.
     *
     * @return list<string>  e.g. ['view', 'create'] when read+write flags are on.
     */
    public function allowedActions(ModulePermission $permission): array
    {
        $allFlags = array_merge($permission->flags(), $permission->extraFlags());

        $actions = [];
        foreach ($this->registry->all() as $flag => $action) {
            if (($allFlags[$flag] ?? null) === true) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    /**
     * Return the canonical list of action names this resolver manages.
     * Includes the 5 native + any custom-registered action so the
     * synchronizer can scope grant/revoke plans to the full managed
     * set.
     *
     * @return list<string>
     */
    public function managedActions(): array
    {
        return $this->registry->actions();
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
