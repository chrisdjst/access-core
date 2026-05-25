<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use ModularizeRbac\Core\Application\Module\ModuleFilter;
use ModularizeRbac\Core\Application\Shared\PaginatedResult;
use ModularizeRbac\Core\Application\Shared\Pagination;
use ModularizeRbac\Core\Domain\Module\Module;
use ModularizeRbac\Core\Domain\Module\ModuleSlug;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Persistence port for {@see Module} aggregates. Adapter implementations
 * are responsible for soft-delete filtering, eager-loading translations
 * as needed, and respecting the (slug) uniqueness invariant.
 *
 * `withChildren()` is a deliberately separate read method so the host
 * can choose to eager-load the menu tree in one query rather than
 * walking parent links N times.
 */
interface ModuleRepository
{
    public function find(Uuid $id): ?Module;

    public function findBySlug(ModuleSlug $slug): ?Module;

    /**
     * Return all non-deleted modules ordered by (rootModuleId NULLS FIRST,
     * sortOrder ASC). Suitable for rendering the admin menu tree.
     *
     * @return list<Module>
     */
    public function allActiveTree(): array;

    public function save(Module $module): void;

    /**
     * Windowed search over modules with an optional filter set.
     *
     * Implementations MUST return both the windowed slice (respecting
     * `pagination->limit` and `pagination->offset`) AND the total
     * count of rows that matched the filter — callers paginate UIs
     * off the same answer.
     *
     * Modules soft-deleted via `deletedAt` are EXCLUDED unless the
     * filter explicitly includes them in a future revision; the v1.8
     * contract does not expose that toggle.
     *
     * @return PaginatedResult<Module>
     */
    public function searchPaginated(ModuleFilter $filter, Pagination $pagination): PaginatedResult;
}
