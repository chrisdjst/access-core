<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Role\Role;

final readonly class RoleOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $displayName,
        public string $guard,
        public ?string $tenantId,
        public int $level,
        public bool $isSystem,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    public static function fromEntity(Role $role): self
    {
        return new self(
            id: $role->id->value,
            name: $role->name(),
            displayName: $role->displayName(),
            guard: $role->guard()->value,
            tenantId: $role->tenantId()?->value,
            level: $role->level()->value,
            isSystem: $role->isSystem(),
            createdAt: $role->createdAt(),
            updatedAt: $role->updatedAt(),
        );
    }
}
