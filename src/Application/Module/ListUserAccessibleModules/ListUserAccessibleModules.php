<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\ListUserAccessibleModules;

use ModularizeRbac\Core\Application\Module\ModuleOutput;
use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\RoleModulePermissionRepository;
use ModularizeRbac\Core\Application\Ports\UserRoleResolver;
use ModularizeRbac\Core\Domain\Module\ModulePermission;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Read-only use-case: returns the distinct list of modules a user
 * can effectively access — i.e. modules where at least one of the
 * user's roles has at least one allowed flag.
 *
 * Typical caller: a front-end shell rendering the user's navigation
 * tree on login. Tree ordering matches {@see \ModularizeRbac\Core\Domain\Module\Module}'s
 * sort_order convention.
 *
 * Authorization: this use-case is meant to be called for the
 * currently authenticated user — the caller's `Authorizer` ensures
 * they're allowed to query their own access map. Callers querying
 * for a different user should gate with `admin.users.view` ahead of
 * invoking; we don't second-guess that here.
 */
final class ListUserAccessibleModules
{
    public function __construct(
        private readonly UserRoleResolver $userRoles,
        private readonly RoleModulePermissionRepository $bindings,
        private readonly Authorizer $authorizer,
    ) {
    }

    /**
     * @return list<ModuleOutput>
     */
    public function execute(string $rawUserId): array
    {
        $this->authorizer->ensure('admin.modules.view');

        $userId = new Uuid($rawUserId);
        $roleIds = $this->userRoles->roleIdsFor($userId);
        if ($roleIds === []) {
            return [];
        }

        $byModuleId = [];
        foreach ($roleIds as $roleId) {
            foreach ($this->bindings->matrixFor($roleId) as $row) {
                if (! $this->hasAnyAllowed($row->permission)) {
                    continue;
                }
                $byModuleId[$row->module->id->value] = $row->module;
            }
        }

        $modules = array_values($byModuleId);
        usort($modules, static function ($a, $b): int {
            $rootA = $a->rootModuleId()?->value;
            $rootB = $b->rootModuleId()?->value;
            if ($rootA !== $rootB) {
                if ($rootA === null) {
                    return -1;
                }
                if ($rootB === null) {
                    return 1;
                }

                return $rootA <=> $rootB;
            }

            return $a->sortOrder() <=> $b->sortOrder();
        });

        $output = [];
        foreach ($modules as $module) {
            $output[] = ModuleOutput::fromEntity($module);
        }

        return $output;
    }

    private function hasAnyAllowed(ModulePermission $permission): bool
    {
        foreach ($permission->flags() as $allowed) {
            if ($allowed) {
                return true;
            }
        }

        return false;
    }
}
