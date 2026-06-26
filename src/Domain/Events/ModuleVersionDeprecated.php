<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Events;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Module\ModuleVersion;
use ModularizeRbac\Core\Domain\Shared\DomainEvent;
use ModularizeRbac\Core\Domain\Shared\Uuid;

final readonly class ModuleVersionDeprecated implements DomainEvent
{
    public function __construct(
        public Uuid $versionId,
        public Uuid $moduleId,
        public ModuleVersion $version,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
