<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\ListModules;

use ModularizeRbac\Core\Application\Module\ModuleOutput;
use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\ModuleRepository;

/**
 * Use-case: list all active modules, ready to render as a tree.
 * Returns a flat array — the HTTP adapter or front-end groups by
 * `rootModuleId` to build the nesting.
 *
 * Authorization: `admin.modules.view`.
 */
final class ListModules
{
    public function __construct(
        private readonly ModuleRepository $modules,
        private readonly Authorizer $authorizer,
    ) {
    }

    /**
     * @return list<ModuleOutput>
     */
    public function execute(): array
    {
        $this->authorizer->ensure('admin.modules.view');

        $list = [];
        foreach ($this->modules->allActiveTree() as $module) {
            $list[] = ModuleOutput::fromEntity($module);
        }

        return $list;
    }
}
