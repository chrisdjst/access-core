<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Module\Channel;
use ModularizeRbac\Core\Domain\Shared\Uuid;

interface ModuleVersionPromotionRepository
{
    public function append(
        Uuid $id,
        Uuid $moduleVersionId,
        Uuid $moduleId,
        ?Channel $channelBefore,
        Channel $channelAfter,
        string $changeType,
        ?Uuid $actorId,
        DateTimeImmutable $changedAt,
    ): void;
}
