<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\ListRoles;

use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\RoleRepository;
use ModularizeRbac\Core\Application\Role\RoleFilter;
use ModularizeRbac\Core\Application\Role\RoleOutput;
use ModularizeRbac\Core\Application\Shared\PaginatedResult;
use ModularizeRbac\Core\Application\Shared\Pagination;

/**
 * Windowed counterpart of {@see ListRoles}. Same authorization rule
 * (`admin.roles.view`); takes a {@see RoleFilter} + {@see Pagination}
 * and returns the windowed slice plus the total count.
 */
final class ListRolesPaginated
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly Authorizer $authorizer,
    ) {
    }

    /**
     * @return PaginatedResult<RoleOutput>
     */
    public function execute(RoleFilter $filter, Pagination $pagination): PaginatedResult
    {
        $this->authorizer->ensure('admin.roles.view');

        $page = $this->roles->searchPaginated($filter, $pagination);

        $items = [];
        foreach ($page->items as $role) {
            $items[] = RoleOutput::fromEntity($role);
        }

        return new PaginatedResult($items, $page->total, $page->pagination);
    }
}
