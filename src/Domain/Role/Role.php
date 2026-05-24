<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Role;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\RecordsEvents;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Aggregate root for a role. A role is scoped to a (guard, tenant)
 * tuple — `tenantId === null` means the role is global (not owned by
 * any tenant). The tenant model itself lives in the host application;
 * the domain only carries its id.
 *
 * `isSystem` flags roles seeded by the host (e.g. "super-admin") that
 * should resist deletion or renaming through normal use-cases —
 * enforcement of that constraint lives in the application layer.
 *
 * Optional `parentRoleId` enables role hierarchies: a child role
 * conceptually inherits its ancestor's bindings. Enforcement of the
 * inheritance semantic lives in adapter layers (e.g. the bridge's
 * `HasAccessPermissions::canAccess()`); the aggregate just stores the
 * pointer and refuses to point at itself directly.
 */
final class Role
{
    use RecordsEvents;

    private function __construct(
        public readonly Uuid $id,
        private string $name,
        private ?string $displayName,
        private GuardName $guard,
        private ?Uuid $tenantId,
        private RoleLevel $level,
        private bool $isSystem,
        private ?Uuid $parentRoleId,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        Uuid $id,
        string $name,
        ?string $displayName,
        GuardName $guard,
        ?Uuid $tenantId,
        RoleLevel $level,
        bool $isSystem,
        Clock $clock,
        ?Uuid $parentRoleId = null,
    ): self {
        if ($parentRoleId !== null && $parentRoleId->equals($id)) {
            throw InvalidInput::of('parent_role_id', 'A role cannot be its own parent.');
        }

        $now = $clock->now();

        return new self(
            id: $id,
            name: $name,
            displayName: $displayName,
            guard: $guard,
            tenantId: $tenantId,
            level: $level,
            isSystem: $isSystem,
            parentRoleId: $parentRoleId,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function reconstitute(
        Uuid $id,
        string $name,
        ?string $displayName,
        GuardName $guard,
        ?Uuid $tenantId,
        RoleLevel $level,
        bool $isSystem,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?Uuid $parentRoleId = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            displayName: $displayName,
            guard: $guard,
            tenantId: $tenantId,
            level: $level,
            isSystem: $isSystem,
            parentRoleId: $parentRoleId,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function displayName(): ?string
    {
        return $this->displayName;
    }

    public function guard(): GuardName
    {
        return $this->guard;
    }

    public function tenantId(): ?Uuid
    {
        return $this->tenantId;
    }

    public function isGlobal(): bool
    {
        return $this->tenantId === null;
    }

    public function level(): RoleLevel
    {
        return $this->level;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function parentRoleId(): ?Uuid
    {
        return $this->parentRoleId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function changeDisplayName(?string $displayName, Clock $clock): void
    {
        if ($this->displayName === $displayName) {
            return;
        }
        $this->displayName = $displayName;
        $this->updatedAt = $clock->now();
    }
}
