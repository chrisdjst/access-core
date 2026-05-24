<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\BulkDeleteModules;

use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\DomainEventDispatcher;
use ModularizeRbac\Core\Application\Ports\ModuleRepository;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Domain\Module\Module;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Exceptions\NotFound;

/**
 * Use-case: soft-delete many modules atomically.
 *
 * All-or-nothing: a single missing id rolls back the whole batch
 * and the caller sees `NotFound` for the first missing entry.
 * Already-deleted modules are silently skipped — the aggregate's
 * `softDelete()` is itself idempotent.
 *
 * Authorization: `admin.modules.delete`.
 */
final class BulkDeleteModules
{
    public function __construct(
        private readonly ModuleRepository $modules,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
        private readonly DomainEventDispatcher $events,
        private readonly Clock $clock,
    ) {
    }

    public function execute(BulkDeleteModulesInput $input): void
    {
        $this->authorizer->ensure('admin.modules.delete');

        $modules = [];
        foreach ($input->ids as $id) {
            $module = $this->modules->find($id) ?? throw NotFound::of('Module', $id->value);
            $modules[] = $module;
        }

        $this->uow->transactional(function () use ($modules): void {
            foreach ($modules as $module) {
                $module->softDelete($this->authorizer->actorId(), $this->clock);
                $this->modules->save($module);
            }
        });

        foreach ($modules as $module) {
            $this->events->dispatchAll($module->pullDomainEvents());
        }
    }
}
