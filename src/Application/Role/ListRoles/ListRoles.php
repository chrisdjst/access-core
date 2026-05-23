<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\ListRoles;

use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\RoleRepository;
use ModularizeRbac\Core\Application\Role\RoleOutput;
use ModularizeRbac\Core\Domain\Role\GuardName;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Use-case: list roles, optionally filtered by guard and tenant.
 *
 * Authorization: `admin.roles.view`.
 */
final class ListRoles
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly Authorizer $authorizer,
    ) {
    }

    /**
     * @return list<RoleOutput>
     */
    public function execute(?string $guard, ?string $tenantId): array
    {
        $this->authorizer->ensure('admin.roles.view');

        $guardVo = $guard !== null ? new GuardName($guard) : null;
        $tenantVo = $tenantId !== null ? new Uuid($tenantId) : null;

        $output = [];
        foreach ($this->roles->search($guardVo, $tenantVo) as $role) {
            $output[] = RoleOutput::fromEntity($role);
        }

        return $output;
    }
}
