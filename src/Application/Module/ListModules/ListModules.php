<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Module\ListModules;

use Modularize\Access\Application\Module\ModuleOutput;
use Modularize\Access\Application\Ports\Authorizer;
use Modularize\Access\Application\Ports\ModuleRepository;

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
