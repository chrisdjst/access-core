<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Role\ListRoles;

use Modularize\Access\Application\Ports\Authorizer;
use Modularize\Access\Application\Ports\RoleRepository;
use Modularize\Access\Application\Role\RoleOutput;
use Modularize\Access\Domain\Role\GuardName;
use Modularize\Access\Domain\Shared\Uuid;

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
