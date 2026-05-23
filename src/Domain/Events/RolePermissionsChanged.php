<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Events;

use DateTimeImmutable;
use ModularizeRbac\Core\Domain\Permission\PermissionName;
use ModularizeRbac\Core\Domain\Role\GuardName;
use ModularizeRbac\Core\Domain\Shared\DomainEvent;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Emitted when a role's effective permission set changes for a given
 * module. Carries the explicit list of permission names that were
 * granted and revoked in this transaction so subscribers (e.g. the
 * optional Spatie adapter in PR 5) can replicate the change without
 * re-deriving it from flag state.
 */
final readonly class RolePermissionsChanged implements DomainEvent
{
    /**
     * @param  list<PermissionName>  $granted
     * @param  list<PermissionName>  $revoked
     */
    public function __construct(
        public Uuid $roleId,
        public GuardName $guard,
        public Uuid $moduleId,
        public array $granted,
        public array $revoked,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
