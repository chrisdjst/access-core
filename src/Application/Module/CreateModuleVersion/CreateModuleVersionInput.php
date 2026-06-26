<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\CreateModuleVersion;

use ModularizeRbac\Core\Domain\Module\Channel;
use ModularizeRbac\Core\Domain\Module\ModuleVersion;
use ModularizeRbac\Core\Domain\Shared\Uuid;

final readonly class CreateModuleVersionInput
{
    public Uuid $moduleId;
    public ModuleVersion $version;
    public Channel $channel;
    public ?array $manifest;

    public function __construct(
        string $moduleId,
        string $version,
        string $channel,
        ?array $manifest = null,
    ) {
        $this->moduleId = new Uuid($moduleId);
        $this->version = ModuleVersion::fromString($version);
        $this->channel = Channel::from($channel);
        $this->manifest = $manifest;
    }
}
