<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Module\ShowModule;

use Modularize\Access\Application\Module\ModuleOutput;
use Modularize\Access\Application\Ports\Authorizer;
use Modularize\Access\Application\Ports\ModuleRepository;
use Modularize\Access\Domain\Shared\Uuid;
use Modularize\Access\Exceptions\NotFound;

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
