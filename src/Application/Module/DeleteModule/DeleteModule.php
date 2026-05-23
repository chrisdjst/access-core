<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\DeleteModule;

use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\DomainEventDispatcher;
use ModularizeRbac\Core\Application\Ports\ModuleRepository;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\NotFound;

/**
 * Use-case: soft-delete a module. The aggregate marks itself deleted
 * and emits a `ModuleDeleted` event so subscribers can clean up
 * dependent state (e.g. revoke permissions in the external system).
 *
 * Authorization: `admin.modules.delete`.
 */
final class DeleteModule
{
    public function __construct(
        private readonly ModuleRepository $modules,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
        private readonly DomainEventDispatcher $events,
        private readonly Clock $clock,
    ) {
    }

    public function execute(string $rawId): void
    {
        $this->authorizer->ensure('admin.modules.delete');

        $id = new Uuid($rawId);
        $module = $this->modules->find($id) ?? throw NotFound::of('Module', $id->value);

        $this->uow->transactional(function () use ($module): void {
            $module->softDelete($this->authorizer->actorId(), $this->clock);
            $this->modules->save($module);
        });

        $this->events->dispatchAll($module->pullDomainEvents());
    }
}
