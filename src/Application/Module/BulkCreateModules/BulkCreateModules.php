<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\BulkCreateModules;

use ModularizeRbac\Core\Application\Module\ModuleOutput;
use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\DomainEventDispatcher;
use ModularizeRbac\Core\Application\Ports\ModuleRepository;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Domain\Module\Module;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\IdGenerator;
use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Use-case: create many modules atomically.
 *
 * All-or-nothing semantics: if any entry violates an invariant
 * (existing slug, missing parent, etc.) the whole transaction is
 * rolled back. Domain events are dispatched only after the commit
 * succeeds.
 *
 * Authorization: `admin.modules.create`.
 */
final class BulkCreateModules
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

    /**
     * @return list<ModuleOutput>
     */
    public function execute(BulkCreateModulesInput $input): array
    {
        $this->authorizer->ensure('admin.modules.create');

        // Pre-flight existence checks outside the transaction so a bad
        // payload never opens a write transaction at all.
        foreach ($input->modules as $i => $entry) {
            if ($this->modules->findBySlug($entry->slug) !== null) {
                throw InvalidInput::of(
                    "modules.{$i}.slug",
                    "Module slug already exists: {$entry->slug->value}",
                );
            }
            if ($entry->rootModuleId !== null && $this->modules->find($entry->rootModuleId) === null) {
                throw InvalidInput::of(
                    "modules.{$i}.root_module_id",
                    "Parent module not found: {$entry->rootModuleId->value}",
                );
            }
        }

        /** @var list<Module> $created */
        $created = $this->uow->transactional(function () use ($input): array {
            $created = [];
            foreach ($input->modules as $entry) {
                $module = Module::create(
                    id: $this->ids->nextUuid(),
                    slug: $entry->slug,
                    name: $entry->name,
                    redirect: $entry->redirect,
                    icon: $entry->icon,
                    rootModuleId: $entry->rootModuleId,
                    sortOrder: $entry->sortOrder,
                    isActive: $entry->isActive,
                    createdBy: $this->authorizer->actorId(),
                    clock: $this->clock,
                );
                $this->modules->save($module);
                $created[] = $module;
            }

            return $created;
        });

        foreach ($created as $module) {
            $this->events->dispatchAll($module->pullDomainEvents());
        }

        return array_map(static fn (Module $m): ModuleOutput => ModuleOutput::fromEntity($m), $created);
    }
}
