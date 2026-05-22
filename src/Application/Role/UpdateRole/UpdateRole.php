<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Role\UpdateRole;

use Modularize\Access\Application\Ports\Authorizer;
use Modularize\Access\Application\Ports\RoleRepository;
use Modularize\Access\Application\Ports\UnitOfWork;
use Modularize\Access\Application\Role\RoleOutput;
use Modularize\Access\Domain\Shared\Clock;
use Modularize\Access\Exceptions\NotFound;

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
