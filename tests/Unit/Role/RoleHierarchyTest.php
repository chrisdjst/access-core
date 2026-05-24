<?php

declare(strict_types=1);

use ModularizeRbac\Core\Domain\Role\GuardName;
use ModularizeRbac\Core\Domain\Role\Role;
use ModularizeRbac\Core\Domain\Role\RoleLevel;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Tests\Unit\TestDoubles\FixedClock;

function makeHierarchyRole(string $id, ?string $parentId = null): Role
{
    return Role::create(
        id: new Uuid($id),
        name: 'editor',
        displayName: 'Editor',
        guard: new GuardName('admin'),
        tenantId: null,
        level: new RoleLevel(0),
        isSystem: false,
        clock: FixedClock::at('2026-01-01T00:00:00Z'),
        parentRoleId: $parentId !== null ? new Uuid($parentId) : null,
    );
}

it('defaults parentRoleId to null when no parent is provided', function (): void {
    $role = makeHierarchyRole('11111111-1111-1111-1111-111111111111');

    expect($role->parentRoleId())->toBeNull();
});

it('stores the parentRoleId when provided at creation', function (): void {
    $role = makeHierarchyRole(
        '11111111-1111-1111-1111-111111111111',
        '22222222-2222-2222-2222-222222222222',
    );

    expect($role->parentRoleId())->not->toBeNull()
        ->and($role->parentRoleId()->value)->toBe('22222222-2222-2222-2222-222222222222');
});

it('refuses to create a role whose parentRoleId equals its own id', function (): void {
    $self = '11111111-1111-1111-1111-111111111111';
    expect(fn () => makeHierarchyRole($self, $self))->toThrow(InvalidInput::class);
});
