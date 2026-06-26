<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\PromoteModuleVersion;

use ModularizeRbac\Core\Application\Module\ModuleVersionData;
use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\DomainEventDispatcher;
use ModularizeRbac\Core\Application\Ports\ModuleVersionPromotionRepository;
use ModularizeRbac\Core\Application\Ports\ModuleVersionRepository;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Domain\Events\ModuleVersionPromoted;
use ModularizeRbac\Core\Domain\Module\Channel;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\IdGenerator;
use ModularizeRbac\Core\Exceptions\NotFound;

/**
 * Promotes a module version to the next channel in the lattice (alpha→beta→stable).
 * When promoting to stable, the current stable version (if any) is atomically
 * demoted to beta inside the same transaction, preserving the "one stable" invariant.
 */
final class PromoteModuleVersion
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

    public function execute(PromoteModuleVersionInput $input): ModuleVersionData
    {
        $this->authorizer->ensure('admin.modules.versions.promote');

        $version = $this->versions->find($input->versionId);
        if ($version === null) {
            throw NotFound::of('module_version', $input->versionId->value);
        }

        $version->channel->assertCanPromoteTo($input->toChannel);

        $now = $this->clock->now();
        $channelBefore = $version->channel;

        $promoted = $this->uow->transactional(function () use ($version, $input, $now): ModuleVersionData {
            // When promoting to stable, demote the existing stable version first.
            if ($input->toChannel === Channel::Stable) {
                $currentStable = $this->versions->findStableByModule($version->moduleId);
                if ($currentStable !== null && ! $currentStable->id->equals($version->id)) {
                    $demoted = new ModuleVersionData(
                        id: $currentStable->id,
                        moduleId: $currentStable->moduleId,
                        version: $currentStable->version,
                        channel: Channel::Beta,
                        isActive: $currentStable->isActive,
                        manifest: $currentStable->manifest,
                        createdBy: $currentStable->createdBy,
                        deprecatedAt: $currentStable->deprecatedAt,
                        createdAt: $currentStable->createdAt,
                        updatedAt: $now,
                    );
                    $this->versions->save($demoted);
                    $this->promotions->append(
                        id: $this->ids->nextUuid(),
                        moduleVersionId: $currentStable->id,
                        moduleId: $currentStable->moduleId,
                        channelBefore: Channel::Stable,
                        channelAfter: Channel::Beta,
                        changeType: 'demote',
                        actorId: $this->authorizer->actorId(),
                        changedAt: $now,
                    );
                }
            }

            $updated = new ModuleVersionData(
                id: $version->id,
                moduleId: $version->moduleId,
                version: $version->version,
                channel: $input->toChannel,
                isActive: $version->isActive,
                manifest: $version->manifest,
                createdBy: $version->createdBy,
                deprecatedAt: $version->deprecatedAt,
                createdAt: $version->createdAt,
                updatedAt: $now,
            );
            $this->versions->save($updated);
            $this->promotions->append(
                id: $this->ids->nextUuid(),
                moduleVersionId: $version->id,
                moduleId: $version->moduleId,
                channelBefore: $version->channel,
                channelAfter: $input->toChannel,
                changeType: 'promote',
                actorId: $this->authorizer->actorId(),
                changedAt: $now,
            );

            return $updated;
        });

        $this->events->dispatch(new ModuleVersionPromoted(
            versionId: $promoted->id,
            moduleId: $promoted->moduleId,
            channelBefore: $channelBefore,
            channelAfter: $input->toChannel,
            occurredAt: $now,
        ));

        return $promoted;
    }
}
