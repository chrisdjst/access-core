<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Role\AssignUsersToRole\AssignUsersToRole;
use ModularizeRbac\Core\Application\Role\AssignUsersToRole\AssignUsersToRoleInput;
use ModularizeRbac\Core\Domain\Role\GuardName;
use ModularizeRbac\Core\Domain\Role\Role;
use ModularizeRbac\Core\Domain\Role\RoleLevel;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Exceptions\NotFound;
use ModularizeRbac\Core\Tests\Application\Doubles\AllowingAuthorizer;
use ModularizeRbac\Core\Tests\Application\Doubles\FixedClock;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryRoleRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryUserRoleAssigner;
use ModularizeRbac\Core\Tests\Application\Doubles\PassthroughUnitOfWork;

function assignStack(): array
{
    $roles = new InMemoryRoleRepository();
    $assigner = new InMemoryUserRoleAssigner();
    $auth = new AllowingAuthorizer();
    $clock = FixedClock::at('2026-01-01T00:00:00Z');

    $use = new AssignUsersToRole(
        roles: $roles,
        assigner: $assigner,
        authorizer: $auth,
        uow: new PassthroughUnitOfWork(),
    );

    $role = Role::create(
        id: new Uuid('11111111-1111-1111-1111-111111111111'),
        name: 'editor',
        displayName: 'Editor',
        guard: new GuardName('admin'),
        tenantId: null,
        level: new RoleLevel(50),
        isSystem: false,
        clock: $clock,
    );
    $roles->save($role);

    return compact('use', 'role', 'assigner', 'roles');
}

it('records one assignment per user_id in the payload', function (): void {
    ['use' => $use, 'role' => $role, 'assigner' => $assigner] = assignStack();

    $use->execute(new AssignUsersToRoleInput(
        roleId: $role->id->value,
        userIds: [
            '22222222-2222-2222-2222-222222222222',
            '33333333-3333-3333-3333-333333333333',
        ],
    ));

    expect($assigner->assignments)->toHaveCount(2)
        ->and($assigner->assignments[0]['roleId'])->toBe($role->id->value)
        ->and($assigner->assignments[0]['userId'])->toBe('22222222-2222-2222-2222-222222222222')
        ->and($assigner->assignments[1]['userId'])->toBe('33333333-3333-3333-3333-333333333333')
        ->and($assigner->assignments[0]['tenantId'])->toBeNull();
});

it('forwards tenant_id when provided', function (): void {
    ['use' => $use, 'role' => $role, 'assigner' => $assigner] = assignStack();

    $use->execute(new AssignUsersToRoleInput(
        roleId: $role->id->value,
        userIds: ['22222222-2222-2222-2222-222222222222'],
        tenantId: '44444444-4444-4444-4444-444444444444',
    ));

    expect($assigner->assignments[0]['tenantId'])->toBe('44444444-4444-4444-4444-444444444444');
});

it('de-duplicates repeated user_ids in the payload', function (): void {
    ['use' => $use, 'role' => $role, 'assigner' => $assigner] = assignStack();

    $use->execute(new AssignUsersToRoleInput(
        roleId: $role->id->value,
        userIds: [
            '22222222-2222-2222-2222-222222222222',
            '22222222-2222-2222-2222-222222222222',
            '33333333-3333-3333-3333-333333333333',
        ],
    ));

    expect($assigner->assignments)->toHaveCount(2);
});

it('throws NotFound when the role does not exist', function (): void {
    ['use' => $use] = assignStack();

    expect(fn () => $use->execute(new AssignUsersToRoleInput(
        roleId: '99999999-9999-9999-9999-999999999999',
        userIds: ['22222222-2222-2222-2222-222222222222'],
    )))->toThrow(NotFound::class);
});

it('rejects an empty user_ids list at the input layer', function (): void {
    expect(fn () => new AssignUsersToRoleInput(
        roleId: '11111111-1111-1111-1111-111111111111',
        userIds: [],
    ))->toThrow(InvalidInput::class);
});
