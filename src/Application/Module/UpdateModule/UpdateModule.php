<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Module\UpdateModule;

use Modularize\Access\Application\Module\ModuleOutput;
use Modularize\Access\Application\Ports\Authorizer;
use Modularize\Access\Application\Ports\DomainEventDispatcher;
use Modularize\Access\Application\Ports\ModuleRepository;
use Modularize\Access\Application\Ports\UnitOfWork;
use Modularize\Access\Domain\Shared\Clock;
use Modularize\Access\Exceptions\InvalidInput;
use Modularize\Access\Exceptions\NotFound;

/**
 * Use-case: edit a module's mutable fields. Slug is immutable. Self
 * parenting and circular hierarchies are rejected here even though
 * the legacy data model only supports a single nesting level — keeps
 * the invariant defensible if the model deepens later.
 *
 * Authorization: `admin.modules.update`.
 */
final class UpdateModule
{
    public function __construct(
        private readonly ModuleRepository $modules,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
        private readonly DomainEventDispatcher $events,
        private readonly Clock $clock,
    ) {
    }

    public function execute(UpdateModuleInput $input): ModuleOutput
    {
        $this->authorizer->ensure('admin.modules.update');

        $module = $this->modules->find($input->id) ?? throw NotFound::of('Module', $input->id->value);

        if ($input->rootModuleId !== null) {
            if ($input->rootModuleId->equals($input->id)) {
                throw InvalidInput::of('root_module_id', 'A module cannot be its own parent.');
            }
            $parent = $this->modules->find($input->rootModuleId)
                ?? throw InvalidInput::of('root_module_id', "Parent module not found: {$input->rootModuleId->value}");

            if ($parent->rootModuleId() !== null) {
                throw InvalidInput::of(
                    'root_module_id',
                    'Module hierarchy is limited to one level — parent must itself be a root module.'
                );
            }
        }

        $this->uow->transactional(function () use ($input, $module): void {
            $module->update(
                name: $input->name,
                redirect: $input->redirect,
                icon: $input->icon,
                rootModuleId: $input->rootModuleId,
                sortOrder: $input->sortOrder,
                isActive: $input->isActive,
                updatedBy: $this->authorizer->actorId(),
                clock: $this->clock,
            );
            $this->modules->save($module);
        });

        $this->events->dispatchAll($module->pullDomainEvents());

        return ModuleOutput::fromEntity($module);
    }
}
