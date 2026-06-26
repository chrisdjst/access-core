<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\CreateModuleVersion;

use ModularizeRbac\Core\Application\Module\ModuleVersionData;
use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\DomainEventDispatcher;
use ModularizeRbac\Core\Application\Ports\ModuleRepository;
use ModularizeRbac\Core\Application\Ports\ModuleVersionPromotionRepository;
use ModularizeRbac\Core\Application\Ports\ModuleVersionRepository;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Domain\Events\ModuleVersionCreated;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\IdGenerator;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Exceptions\NotFound;

final class CreateModuleVersion
{
    public function __construct(
        private readonly ModuleRepository $modules,
        private readonly ModuleVersionRepository $versions,
        private readonly ModuleVersionPromotionRepository $promotions,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
        private readonly DomainEventDispatcher $events,
        private readonly IdGenerator $ids,
        private readonly Clock $clock,
    ) {
    }

    public function execute(CreateModuleVersionInput $input): ModuleVersionData
    {
        $this->authorizer->ensure('admin.modules.versions.create');

        if ($this->modules->find($input->moduleId) === null) {
            throw NotFound::of('module', $input->moduleId->value);
        }

        if ($this->versions->findByModuleAndVersion($input->moduleId, $input->version) !== null) {
            throw InvalidInput::of(
                'version',
                "Version {$input->version} already exists for this module."
            );
        }

        $now = $this->clock->now();
        $versionId = $this->ids->nextUuid();

        $data = new ModuleVersionData(
            id: $versionId,
            moduleId: $input->moduleId,
            version: $input->version,
            channel: $input->channel,
            isActive: true,
            manifest: $input->manifest,
            createdBy: $this->authorizer->actorId(),
            deprecatedAt: null,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->uow->transactional(function () use ($data, $now): void {
            $this->versions->save($data);
            $this->promotions->append(
                id: $this->ids->nextUuid(),
                moduleVersionId: $data->id,
                moduleId: $data->moduleId,
                channelBefore: null,
                channelAfter: $data->channel,
                changeType: 'create',
                actorId: $this->authorizer->actorId(),
                changedAt: $now,
            );
        });

        $this->events->dispatch(new ModuleVersionCreated(
            versionId: $data->id,
            moduleId: $data->moduleId,
            version: $data->version,
            channel: $data->channel,
            occurredAt: $now,
        ));

        return $data;
    }
}
