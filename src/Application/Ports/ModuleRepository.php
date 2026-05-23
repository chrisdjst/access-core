<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

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
}
