<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\RoleModulePermission;

use ModularizeRbac\Core\Domain\Module\ModulePermission;
use ModularizeRbac\Core\Domain\Module\ModuleSlug;
use ModularizeRbac\Core\Domain\Permission\PermissionName;

/**
 * Pure-function domain service that resolves whether an ability is
 * granted to a role considering the module hierarchy.
 *
 * Walks from the requested module's slug up the parent chain. At each
 * level it asks the caller for the flag sets the role(s) hold for that
 * module — if any grants the requested action, the ability is allowed.
 *
 * The lookups are provided as callables so the resolver stays free of
 * persistence concerns. Bridge / host code preloads the data and
 * passes back the per-slug answers; the resolver only orchestrates the
 * walk and the boolean check.
 *
 * Inheritance is opt-in at the host level — when hosts disable it,
 * they simply don't construct + invoke this service. The legacy
 * "binding-must-be-on-this-module" semantic is preserved by skipping
 * the resolver entirely.
 */
final class PermissionInheritanceResolver
{
    public function __construct(
        private readonly PermissionFlagResolver $flagResolver = new PermissionFlagResolver(),
    ) {
    }

    /**
     * @param  callable(ModuleSlug): list<ModulePermission>  $flagsForSlug
     *         Returns every ModulePermission flag set the consulting
     *         role(s) hold for the given module. Empty list = no binding.
     * @param  callable(ModuleSlug): ?ModuleSlug  $parentOfSlug
     *         Returns the parent slug of `$slug`, or null when `$slug`
     *         is a root module (or not found — same semantic: stop
     *         walking).
     */
    public function isAllowed(
        PermissionName $ability,
        callable $flagsForSlug,
        callable $parentOfSlug,
    ): bool {
        $current = $ability->moduleSlug;
        $visited = [];

        while ($current !== null) {
            // Defensive cycle break: a malformed parent chain (self or
            // a loop) must not spin forever even though the domain
            // doesn't expect cycles. We just stop walking; the answer
            // is "not allowed via this chain."
            if (isset($visited[$current->value])) {
                return false;
            }
            $visited[$current->value] = true;

            foreach ($flagsForSlug($current) as $flags) {
                $allowed = $this->flagResolver->allowedActions($flags);
                if (in_array($ability->action, $allowed, true)) {
                    return true;
                }
            }

            $current = $parentOfSlug($current);
        }

        return false;
    }
}
