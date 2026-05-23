<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\ShowModule;

use ModularizeRbac\Core\Application\Module\ModuleOutput;
use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\ModuleRepository;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\NotFound;

/**
 * Use-case: fetch a single module.
 *
 * Authorization: `admin.modules.view`.
 */
final class ShowModule
{
    public function __construct(
        private readonly ModuleRepository $modules,
        private readonly Authorizer $authorizer,
    ) {
    }

    public function execute(string $rawId): ModuleOutput
    {
        $this->authorizer->ensure('admin.modules.view');

        $id = new Uuid($rawId);
        $module = $this->modules->find($id) ?? throw NotFound::of('Module', $id->value);

        return ModuleOutput::fromEntity($module);
    }
}
