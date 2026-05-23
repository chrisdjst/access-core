<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\ShowRole;

use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\RoleRepository;
use ModularizeRbac\Core\Application\Role\RoleOutput;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\NotFound;

/**
 * Use-case: fetch a single role. The flag-matrix payload (per-module
 * permissions) is delivered by a separate read use-case in the
 * legacy contract, but the HTTP adapter currently composes it
 * inline; PR 4 will reshape the controller to align with the
 * use-case boundary.
 *
 * Authorization: `admin.roles.view`.
 */
final class ShowRole
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly Authorizer $authorizer,
    ) {
    }

    public function execute(string $rawId): RoleOutput
    {
        $this->authorizer->ensure('admin.roles.view');

        $id = new Uuid($rawId);
        $role = $this->roles->find($id) ?? throw NotFound::of('Role', $id->value);

        return RoleOutput::fromEntity($role);
    }
}
