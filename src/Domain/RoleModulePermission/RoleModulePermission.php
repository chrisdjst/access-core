<?php

declare(strict_types=1);

namespace Modularize\Access\Domain\RoleModulePermission;

use DateTimeImmutable;
use Modularize\Access\Domain\Shared\Clock;
use Modularize\Access\Domain\Shared\Uuid;

/**
 * Join entity binding a {@see \Modularize\Access\Domain\Role\Role} to a
 * {@see \Modularize\Access\Domain\Module\Module} via a specific
 * {@see \Modularize\Access\Domain\Module\ModulePermission} flag set.
 *
 * The (roleId, moduleId) tuple is unique by domain rule. Changing
 * what permissions a role has on a module means updating
 * `modulePermissionId` to point at a fresh ModulePermission row —
 * historical flag sets are preserved.
 */
final class RoleModulePermission
{
    public function __construct(
        public readonly Uuid $id,
        public readonly Uuid $roleId,
        public readonly Uuid $moduleId,
        private Uuid $modulePermissionId,
        public readonly ?Uuid $createdBy,
        private ?Uuid $updatedBy,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        Uuid $id,
        Uuid $roleId,
        Uuid $moduleId,
        Uuid $modulePermissionId,
        ?Uuid $createdBy,
        Clock $clock,
    ): self {
        $now = $clock->now();

        return new self(
            id: $id,
            roleId: $roleId,
            moduleId: $moduleId,
            modulePermissionId: $modulePermissionId,
            createdBy: $createdBy,
            updatedBy: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function modulePermissionId(): Uuid
    {
        return $this->modulePermissionId;
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

    public function pointToPermission(Uuid $modulePermissionId, ?Uuid $updatedBy, Clock $clock): void
    {
        if ($this->modulePermissionId->equals($modulePermissionId)) {
            return;
        }
        $this->modulePermissionId = $modulePermissionId;
        $this->updatedBy = $updatedBy;
        $this->updatedAt = $clock->now();
    }
}
