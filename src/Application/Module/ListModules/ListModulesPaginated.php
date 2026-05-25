<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\ListModules;

use ModularizeRbac\Core\Application\Module\ModuleFilter;
use ModularizeRbac\Core\Application\Module\ModuleOutput;
use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\ModuleRepository;
use ModularizeRbac\Core\Application\Shared\PaginatedResult;
use ModularizeRbac\Core\Application\Shared\Pagination;

/**
 * Windowed counterpart of {@see ListModules}. Authorizes the same
 * ability, delegates the heavy lifting to
 * {@see ModuleRepository::searchPaginated()}, and returns the result
 * carrying ModuleOutput projections (so HTTP adapters serialize the
 * same shape they already use for the non-paginated path).
 */
final class ListModulesPaginated
{
    public function __construct(
        private readonly ModuleRepository $modules,
        private readonly Authorizer $authorizer,
    ) {
    }

    /**
     * @return PaginatedResult<ModuleOutput>
     */
    public function execute(ModuleFilter $filter, Pagination $pagination): PaginatedResult
    {
        $this->authorizer->ensure('admin.modules.view');

        $page = $this->modules->searchPaginated($filter, $pagination);

        $items = [];
        foreach ($page->items as $module) {
            $items[] = ModuleOutput::fromEntity($module);
        }

        return new PaginatedResult($items, $page->total, $page->pagination);
    }
}
