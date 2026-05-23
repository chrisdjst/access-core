<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\UpdateRole;

use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\RoleRepository;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Application\Role\RoleOutput;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Exceptions\NotFound;

/**
 * Use-case: update a role's mutable identity fields. Currently only
 * `displayName`; future fields land here as the role model grows.
 *
 * Authorization: `admin.roles.update`.
 */
final class UpdateRole
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
        private readonly Clock $clock,
    ) {
    }

    public function execute(UpdateRoleInput $input): RoleOutput
    {
        $this->authorizer->ensure('admin.roles.update');

        $role = $this->roles->find($input->id) ?? throw NotFound::of('Role', $input->id->value);

        $this->uow->transactional(function () use ($role, $input): void {
            $role->changeDisplayName($input->displayName, $this->clock);
            $this->roles->save($role);
        });

        return RoleOutput::fromEntity($role);
    }
}
