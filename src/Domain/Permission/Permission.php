<?php

declare(strict_types=1);

namespace Modularize\Access\Domain\Permission;

use DateTimeImmutable;
use Modularize\Access\Domain\Module\ModuleSlug;
use Modularize\Access\Domain\Role\GuardName;
use Modularize\Access\Domain\Shared\Clock;
use Modularize\Access\Domain\Shared\Uuid;

/**
 * A single capability identified by (name, guard). Multiple guards
 * can host overlapping names — the (name, guard) tuple is unique.
 *
 * Permissions are mostly read-only after creation; the name encodes
 * the meaning. The optional `moduleSlug` is a denormalized hint that
 * lets infrastructure queries scope by module without parsing the
 * name string on every request.
 */
final class Permission
{
    public function __construct(
        public readonly Uuid $id,
        public readonly PermissionName $name,
        public readonly GuardName $guard,
        public readonly ?ModuleSlug $moduleSlug,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        Uuid $id,
        PermissionName $name,
        GuardName $guard,
        ?ModuleSlug $moduleSlug,
        Clock $clock,
    ): self {
        $now = $clock->now();

        return new self(
            id: $id,
            name: $name,
            guard: $guard,
            moduleSlug: $moduleSlug ?? $name->moduleSlug,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(Clock $clock): void
    {
        $this->updatedAt = $clock->now();
    }
}
