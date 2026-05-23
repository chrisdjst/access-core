<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Module\Module;

/**
 * Framework-agnostic projection of a Module aggregate for delivery
 * back across the application boundary. The HTTP adapter turns this
 * into a JsonResource; CLI tools render it directly.
 */
final readonly class ModuleOutput
{
    public function __construct(
        public string $id,
        public string $slug,
        public string $name,
        public ?string $redirect,
        public ?string $icon,
        public ?string $rootModuleId,
        public int $sortOrder,
        public bool $isActive,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $deletedAt,
    ) {
    }

    public static function fromEntity(Module $module): self
    {
        return new self(
            id: $module->id->value,
            slug: $module->slug()->value,
            name: $module->name(),
            redirect: $module->redirect(),
            icon: $module->icon(),
            rootModuleId: $module->rootModuleId()?->value,
            sortOrder: $module->sortOrder(),
            isActive: $module->isActive(),
            createdAt: $module->createdAt(),
            updatedAt: $module->updatedAt(),
            deletedAt: $module->deletedAt(),
        );
    }
}
