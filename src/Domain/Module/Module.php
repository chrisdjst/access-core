<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Module;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Events\ModuleCreated;
use ModularizeRbac\Core\Domain\Events\ModuleDeleted;
use ModularizeRbac\Core\Domain\Events\ModuleUpdated;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\RecordsEvents;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Aggregate root for a feature module exposed to roles. Modules form
 * a single-level hierarchy: a top-level module (`rootModuleId === null`)
 * may have children (`rootModuleId === parent.id`). Deeper nesting is
 * intentionally rejected by the use-case layer; the slug carries any
 * additional path information.
 *
 * Soft deletion is modeled as a timestamp; repositories filter on it.
 * Restoring is not implemented in PR 1 (use-cases will own that flow
 * if needed in a later increment).
 */
final class Module
{
    use RecordsEvents;

    private function __construct(
        public readonly Uuid $id,
        private ModuleSlug $slug,
        private string $name,
        private ?string $redirect,
        private ?string $icon,
        private ?Uuid $rootModuleId,
        private int $sortOrder,
        private bool $isActive,
        public readonly ?Uuid $createdBy,
        private ?Uuid $updatedBy,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt,
    ) {
    }

    public static function create(
        Uuid $id,
        ModuleSlug $slug,
        string $name,
        ?string $redirect,
        ?string $icon,
        ?Uuid $rootModuleId,
        int $sortOrder,
        bool $isActive,
        ?Uuid $createdBy,
        Clock $clock,
    ): self {
        $now = $clock->now();
        $module = new self(
            id: $id,
            slug: $slug,
            name: $name,
            redirect: $redirect,
            icon: $icon,
            rootModuleId: $rootModuleId,
            sortOrder: $sortOrder,
            isActive: $isActive,
            createdBy: $createdBy,
            updatedBy: null,
            createdAt: $now,
            updatedAt: $now,
            deletedAt: null,
        );
        $module->recordEvent(new ModuleCreated($id, $slug, $now));

        return $module;
    }

    /**
     * Hydrate an existing module from persistence. Skips the
     * "create" event recording — used by repositories only.
     */
    public static function reconstitute(
        Uuid $id,
        ModuleSlug $slug,
        string $name,
        ?string $redirect,
        ?string $icon,
        ?Uuid $rootModuleId,
        int $sortOrder,
        bool $isActive,
        ?Uuid $createdBy,
        ?Uuid $updatedBy,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $deletedAt,
    ): self {
        return new self(
            id: $id,
            slug: $slug,
            name: $name,
            redirect: $redirect,
            icon: $icon,
            rootModuleId: $rootModuleId,
            sortOrder: $sortOrder,
            isActive: $isActive,
            createdBy: $createdBy,
            updatedBy: $updatedBy,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
        );
    }

    public function slug(): ModuleSlug
    {
        return $this->slug;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function redirect(): ?string
    {
        return $this->redirect;
    }

    public function icon(): ?string
    {
        return $this->icon;
    }

    public function rootModuleId(): ?Uuid
    {
        return $this->rootModuleId;
    }

    public function isRoot(): bool
    {
        return $this->rootModuleId === null;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function updatedBy(): ?Uuid
    {
        return $this->updatedBy;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    /**
     * Update the mutable fields of the module. The slug is immutable
     * after creation — permission names depend on it.
     */
    public function update(
        string $name,
        ?string $redirect,
        ?string $icon,
        ?Uuid $rootModuleId,
        int $sortOrder,
        bool $isActive,
        ?Uuid $updatedBy,
        Clock $clock,
    ): void {
        $changed = $this->name !== $name
            || $this->redirect !== $redirect
            || $this->icon !== $icon
            || ! self::sameUuid($this->rootModuleId, $rootModuleId)
            || $this->sortOrder !== $sortOrder
            || $this->isActive !== $isActive;

        if (! $changed) {
            return;
        }

        $this->name = $name;
        $this->redirect = $redirect;
        $this->icon = $icon;
        $this->rootModuleId = $rootModuleId;
        $this->sortOrder = $sortOrder;
        $this->isActive = $isActive;
        $this->updatedBy = $updatedBy;
        $this->updatedAt = $clock->now();

        $this->recordEvent(new ModuleUpdated($this->id, $this->slug, $this->updatedAt));
    }

    public function softDelete(?Uuid $updatedBy, Clock $clock): void
    {
        if ($this->isDeleted()) {
            return;
        }
        $now = $clock->now();
        $this->deletedAt = $now;
        $this->updatedBy = $updatedBy;
        $this->updatedAt = $now;

        $this->recordEvent(new ModuleDeleted($this->id, $this->slug, $now));
    }

    private static function sameUuid(?Uuid $a, ?Uuid $b): bool
    {
        if ($a === null && $b === null) {
            return true;
        }
        if ($a === null || $b === null) {
            return false;
        }

        return $a->equals($b);
    }
}
