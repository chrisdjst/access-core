<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\RestoreRole;

use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\RoleRepository;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Application\Role\RoleOutput;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Exceptions\NotFound;

/**
 * Use-case: undo a soft-delete on a role. The role's bindings are
 * preserved as-is (the SoftDelete path doesn't drop them — only the
 * row's `deletedAt` is cleared).
 *
 * Authorization: `admin.roles.delete` — restoring is the inverse of
 * deleting and lives under the same compliance umbrella.
 *
 * Idempotent: restoring a role that wasn't soft-deleted is a no-op
 * at the domain level (aggregate refuses the second call) but still
 * authorizes + reads the row, so callers get a fresh RoleOutput back.
 */
final class RestoreRole
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
        private readonly Clock $clock,
    ) {
    }

    public function execute(string $rawId): RoleOutput
    {
        $this->authorizer->ensure('admin.roles.delete');

        $id = new Uuid($rawId);
        $role = $this->roles->findIncludingTrashed($id)
            ?? throw NotFound::of('Role', $id->value);

        if (! $role->isDeleted()) {
            throw InvalidInput::of('id', 'Role is not soft-deleted.');
        }

        $role->restore($this->clock);
        $this->uow->transactional(function () use ($role): void {
            $this->roles->restore($role);
        });

        return RoleOutput::fromEntity($role);
    }
}
