<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\PromoteModuleVersion;

use ModularizeRbac\Core\Domain\Module\Channel;
use ModularizeRbac\Core\Domain\Shared\Uuid;

final readonly class PromoteModuleVersionInput
{
    public Uuid $versionId;
    public Channel $toChannel;

    public function __construct(string $versionId, string $toChannel)
    {
        $this->versionId = new Uuid($versionId);
        $this->toChannel = Channel::from($toChannel);
    }
}
