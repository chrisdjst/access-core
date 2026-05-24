<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Role\CreateRole\CreateRole;
use ModularizeRbac\Core\Application\Role\CreateRole\CreateRoleInput;
use ModularizeRbac\Core\Domain\Role\GuardName;
use ModularizeRbac\Core\Domain\Role\Role;
use ModularizeRbac\Core\Domain\Role\RoleLevel;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Tests\Application\Doubles\AllowingAuthorizer;
use ModularizeRbac\Core\Tests\Application\Doubles\FixedClock;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryRoleRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\PassthroughUnitOfWork;
use ModularizeRbac\Core\Tests\Application\Doubles\SequentialIdGenerator;

function createRoleStack(): array
{
    $roles = new InMemoryRoleRepository();
    $auth = new AllowingAuthorizer();
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $ids = new SequentialIdGenerator();
    $create = new CreateRole($roles, $auth, new PassthroughUnitOfWork(), $ids, $clock);

    return compact('create', 'roles', 'clock');
}

it('stores parent_role_id on the new role when the parent exists', function (): void {
    ['create' => $create, 'roles' => $roles, 'clock' => $clock] = createRoleStack();

    $parent = Role::create(
        id: new Uuid('11111111-1111-1111-1111-111111111111'),
        name: 'parent',
        displayName: 'Parent',
        guard: new GuardName('admin'),
        tenantId: null,
        level: new RoleLevel(0),
        isSystem: false,
        clock: $clock,
    );
    $roles->save($parent);

    $output = $create->execute(new CreateRoleInput(
        name: 'child',
        displayName: 'Child',
        guard: 'admin',
        tenantId: null,
        parentRoleId: $parent->id->value,
    ));

    expect($output->parentRoleId)->toBe('11111111-1111-1111-1111-111111111111');
});

it('rejects creation when parent_role_id references a missing role', function (): void {
    ['create' => $create] = createRoleStack();

    expect(fn () => $create->execute(new CreateRoleInput(
        name: 'child',
        displayName: 'Child',
        guard: 'admin',
        tenantId: null,
        parentRoleId: '99999999-9999-9999-9999-999999999999',
    )))->toThrow(InvalidInput::class);
});

it('parent_role_id is optional and defaults to null', function (): void {
    ['create' => $create] = createRoleStack();

    $output = $create->execute(new CreateRoleInput(
        name: 'standalone',
        displayName: 'Standalone',
        guard: 'admin',
        tenantId: null,
    ));

    expect($output->parentRoleId)->toBeNull();
});
