<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\AssignUsersToRole;

use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\RoleRepository;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Application\Ports\UserRoleAssigner;
use ModularizeRbac\Core\Application\Role\RoleOutput;
use ModularizeRbac\Core\Exceptions\NotFound;

/**
 * Use-case: bind a set of users to a single role in one transaction.
 *
 * The host's `role_user` pivot is written via the
 * {@see UserRoleAssigner} port; implementations are expected to be
 * idempotent so re-running with the same payload is safe.
 *
 * Authorization: `admin.roles.update`.
 */
final class AssignUsersToRole
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly UserRoleAssigner $assigner,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
    ) {
    }

    public function execute(AssignUsersToRoleInput $input): RoleOutput
    {
        $this->authorizer->ensure('admin.roles.update');

        $role = $this->roles->find($input->roleId) ?? throw NotFound::of('Role', $input->roleId->value);

        $this->uow->transactional(function () use ($input): void {
            foreach ($input->userIds as $userId) {
                $this->assigner->assign($input->roleId, $userId, $input->tenantId);
            }
        });

        return RoleOutput::fromEntity($role);
    }
}
