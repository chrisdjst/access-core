<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module;

use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Optional filter set for {@see \ModularizeRbac\Core\Application\Ports\ModuleRepository::searchPaginated()}.
 *
 * Every field is nullable — null means "don't filter on this".
 * `slugLike` is matched as a case-insensitive substring; adapters
 * should translate it to `LIKE %value%` (or the driver equivalent).
 */
final readonly class ModuleFilter
{
    public ?Uuid $rootModuleId;

    public function __construct(
        public ?bool $isActive = null,
        ?string $rootModuleId = null,
        public ?string $slugLike = null,
    ) {
        $this->rootModuleId = $rootModuleId !== null ? new Uuid($rootModuleId) : null;
    }
}
