<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\CreateRole;

use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\RoleRepository;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Application\Role\RoleOutput;
use ModularizeRbac\Core\Domain\Role\Role;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\IdGenerator;
use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Use-case: register a new role so users can be assigned to it.
 *
 * Authorization: `admin.roles.create`.
 *
 * Enforces uniqueness on (name, guard, tenantId) before insert. The
 * database has a unique constraint as a safety net; failing fast in
 * the application layer surfaces a friendlier 422 to the caller.
 */
final class CreateRole
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
        private readonly IdGenerator $ids,
        private readonly Clock $clock,
    ) {
    }

    public function execute(CreateRoleInput $input): RoleOutput
    {
        $this->authorizer->ensure('admin.roles.create');

        if ($this->roles->findByName($input->name, $input->guard, $input->tenantId) !== null) {
            throw InvalidInput::of(
                'name',
                sprintf(
                    'A role with name "%s" already exists for guard "%s"%s.',
                    $input->name,
                    $input->guard->value,
                    $input->tenantId !== null ? ' in this tenant' : '',
                ),
            );
        }

        $role = $this->uow->transactional(function () use ($input): Role {
            $role = Role::create(
                id: $this->ids->nextUuid(),
                name: $input->name,
                displayName: $input->displayName,
                guard: $input->guard,
                tenantId: $input->tenantId,
                level: $input->level,
                isSystem: $input->isSystem,
                clock: $this->clock,
            );
            $this->roles->save($role);

            return $role;
        });

        return RoleOutput::fromEntity($role);
    }
}
