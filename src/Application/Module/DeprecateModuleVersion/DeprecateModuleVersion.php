<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\DeprecateModuleVersion;

use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\DomainEventDispatcher;
use ModularizeRbac\Core\Application\Ports\ModuleVersionPromotionRepository;
use ModularizeRbac\Core\Application\Ports\ModuleVersionRepository;
use ModularizeRbac\Core\Application\Module\ModuleVersionData;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Domain\Events\ModuleVersionDeprecated;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\IdGenerator;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Exceptions\NotFound;

final class DeprecateModuleVersion
{
    public function __construct(
        private readonly ModuleVersionRepository $versions,
        private readonly ModuleVersionPromotionRepository $promotions,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
        private readonly DomainEventDispatcher $events,
        private readonly IdGenerator $ids,
        private readonly Clock $clock,
    ) {
    }

    public function execute(Uuid $versionId): void
    {
        $this->authorizer->ensure('admin.modules.versions.deprecate');

        $version = $this->versions->find($versionId);
        if ($version === null) {
            throw NotFound::of('module_version', $versionId->value);
        }
        if ($version->isDeprecated()) {
            throw InvalidInput::of('version', 'This version is already deprecated.');
        }

        $now = $this->clock->now();

        $deprecated = new ModuleVersionData(
            id: $version->id,
            moduleId: $version->moduleId,
            version: $version->version,
            channel: $version->channel,
            isActive: false,
            manifest: $version->manifest,
            createdBy: $version->createdBy,
            deprecatedAt: $now,
            createdAt: $version->createdAt,
            updatedAt: $now,
        );

        $this->uow->transactional(function () use ($deprecated, $version, $now): void {
            $this->versions->save($deprecated);
            $this->promotions->append(
                id: $this->ids->nextUuid(),
                moduleVersionId: $version->id,
                moduleId: $version->moduleId,
                channelBefore: $version->channel,
                channelAfter: $version->channel,
                changeType: 'deprecate',
                actorId: $this->authorizer->actorId(),
                changedAt: $now,
            );
        });

        $this->events->dispatch(new ModuleVersionDeprecated(
            versionId: $version->id,
            moduleId: $version->moduleId,
            version: $version->version,
            occurredAt: $now,
        ));
    }
}
