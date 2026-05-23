<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Module;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * A flag set encoding which of the five canonical actions
 * (list/view/create/update/delete) a role is allowed to perform on a
 * module. Stored as a denormalized boolean tuple because the host UI
 * presents it as a matrix of checkboxes; the {@see PermissionFlagResolver}
 * domain service maps these flags into Spatie-style permission names.
 *
 * Each row also carries optional audit ids (created/updated by) so
 * the infrastructure layer can populate them — the domain doesn't
 * care who set the flags, only that they were set.
 */
final class ModulePermission
{
    /**
     * Map from boolean flag name to the canonical action it represents.
     * The order of declaration is the canonical "action order" used by
     * resolvers and serializers.
     *
     * @var array<string, string>
     */
    public const FLAG_TO_ACTION = [
        'isListingAllowed' => 'list',
        'isReadingAllowed' => 'view',
        'isWritingAllowed' => 'create',
        'isEditingAllowed' => 'update',
        'isDeleteAllowed' => 'delete',
    ];

    public function __construct(
        public readonly Uuid $id,
        private bool $isListingAllowed,
        private bool $isReadingAllowed,
        private bool $isWritingAllowed,
        private bool $isEditingAllowed,
        private bool $isDeleteAllowed,
        private bool $isActive,
        public readonly ?Uuid $createdBy,
        private ?Uuid $updatedBy,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        Uuid $id,
        bool $isListingAllowed,
        bool $isReadingAllowed,
        bool $isWritingAllowed,
        bool $isEditingAllowed,
        bool $isDeleteAllowed,
        ?Uuid $createdBy,
        Clock $clock,
    ): self {
        $now = $clock->now();

        return new self(
            id: $id,
            isListingAllowed: $isListingAllowed,
            isReadingAllowed: $isReadingAllowed,
            isWritingAllowed: $isWritingAllowed,
            isEditingAllowed: $isEditingAllowed,
            isDeleteAllowed: $isDeleteAllowed,
            isActive: true,
            createdBy: $createdBy,
            updatedBy: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function isListingAllowed(): bool
    {
        return $this->isListingAllowed;
    }

    public function isReadingAllowed(): bool
    {
        return $this->isReadingAllowed;
    }

    public function isWritingAllowed(): bool
    {
        return $this->isWritingAllowed;
    }

    public function isEditingAllowed(): bool
    {
        return $this->isEditingAllowed;
    }

    public function isDeleteAllowed(): bool
    {
        return $this->isDeleteAllowed;
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

    /**
     * Return the boolean flag set as a name→bool map. Order matches
     * {@see FLAG_TO_ACTION} so callers can zip the two reliably.
     *
     * @return array<string, bool>
     */
    public function flags(): array
    {
        return [
            'isListingAllowed' => $this->isListingAllowed,
            'isReadingAllowed' => $this->isReadingAllowed,
            'isWritingAllowed' => $this->isWritingAllowed,
            'isEditingAllowed' => $this->isEditingAllowed,
            'isDeleteAllowed' => $this->isDeleteAllowed,
        ];
    }

    public function deactivate(?Uuid $updatedBy, Clock $clock): void
    {
        if (! $this->isActive) {
            return;
        }
        $this->isActive = false;
        $this->updatedBy = $updatedBy;
        $this->updatedAt = $clock->now();
    }
}
