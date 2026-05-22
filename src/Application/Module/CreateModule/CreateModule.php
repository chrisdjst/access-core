<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Module\CreateModule;

use Modularize\Access\Application\Module\ModuleOutput;
use Modularize\Access\Application\Ports\Authorizer;
use Modularize\Access\Application\Ports\DomainEventDispatcher;
use Modularize\Access\Application\Ports\ModuleRepository;
use Modularize\Access\Application\Ports\UnitOfWork;
use Modularize\Access\Domain\Module\Module;
use Modularize\Access\Domain\Shared\Clock;
use Modularize\Access\Domain\Shared\IdGenerator;
use Modularize\Access\Exceptions\InvalidInput;

/**
 * Use-case: register a new module so it can be granted to roles.
 *
 * Authorization: `admin.modules.create`.
 * Invariants enforced here: slug uniqueness, parent existence when
 * `rootModuleId` is supplied. Both are repository lookups rather
 * than DB constraints so we can fail fast with a meaningful
 * `InvalidInput` error.
 */
final class CreateModule
{
    public function __construct(
        private readonly ModuleRepository $modules,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
        private readonly DomainEventDispatcher $events,
        private readonly IdGenerator $ids,
        private readonly Clock $clock,
    ) {
    }

    public function execute(CreateModuleInput $input): ModuleOutput
    {
        $this->authorizer->ensure('admin.modules.create');

        if ($this->modules->findBySlug($input->slug) !== null) {
            throw InvalidInput::of('slug', "Module slug already exists: {$input->slug->value}");
        }
        if ($input->rootModuleId !== null && $this->modules->find($input->rootModuleId) === null) {
            throw InvalidInput::of('root_module_id', "Parent module not found: {$input->rootModuleId->value}");
        }

        $module = $this->uow->transactional(function () use ($input): Module {
            $module = Module::create(
                id: $this->ids->nextUuid(),
                slug: $input->slug,
                name: $input->name,
                redirect: $input->redirect,
                icon: $input->icon,
                rootModuleId: $input->rootModuleId,
                sortOrder: $input->sortOrder,
                isActive: $input->isActive,
                createdBy: $this->authorizer->actorId(),
                clock: $this->clock,
            );
            $this->modules->save($module);

            return $module;
        });

        $this->events->dispatchAll($module->pullDomainEvents());

        return ModuleOutput::fromEntity($module);
    }
}
