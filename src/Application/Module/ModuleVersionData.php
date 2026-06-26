<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Module\Channel;
use ModularizeRbac\Core\Domain\Module\ModuleVersion;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Immutable projection of a module_versions row.
 * Used by port interfaces and use-cases in lieu of a full domain aggregate.
 */
final readonly class ModuleVersionData
{
    public function __construct(
        public Uuid $id,
        public Uuid $moduleId,
        public ModuleVersion $version,
        public Channel $channel,
        public bool $isActive,
        public ?array $manifest,
        public ?Uuid $createdBy,
        public ?DateTimeImmutable $deprecatedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    public function isDeprecated(): bool
    {
        return $this->deprecatedAt !== null;
    }
}
