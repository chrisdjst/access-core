<?php

declare(strict_types=1);

use ModularizeRbac\Core\Domain\Role\GuardName;
use ModularizeRbac\Core\Domain\Role\Role;
use ModularizeRbac\Core\Domain\Role\RoleLevel;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Tests\Application\Doubles\FixedClock;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryRoleRepository;

function makeAncestorRole(string $id, ?string $parentId = null): Role
{
    return Role::create(
        id: new Uuid($id),
        name: 'role-'.substr($id, 0, 8),
        displayName: 'Role '.$id,
        guard: new GuardName('admin'),
        tenantId: null,
        level: new RoleLevel(0),
        isSystem: false,
        clock: FixedClock::at('2026-01-01T00:00:00Z'),
        parentRoleId: $parentId !== null ? new Uuid($parentId) : null,
    );
}

it('returns the immediate parent first, root last', function (): void {
    $repo = new InMemoryRoleRepository();
    $root = makeAncestorRole('11111111-1111-1111-1111-111111111111');
    $mid = makeAncestorRole('22222222-2222-2222-2222-222222222222', $root->id->value);
    $leaf = makeAncestorRole('33333333-3333-3333-3333-333333333333', $mid->id->value);
    $repo->save($root);
    $repo->save($mid);
    $repo->save($leaf);

    $ancestors = $repo->resolveAncestors($leaf->id);

    $values = array_map(fn (Uuid $u) => $u->value, $ancestors);
    expect($values)->toBe([$mid->id->value, $root->id->value]);
});

it('returns an empty list for a root role', function (): void {
    $repo = new InMemoryRoleRepository();
    $root = makeAncestorRole('11111111-1111-1111-1111-111111111111');
    $repo->save($root);

    expect($repo->resolveAncestors($root->id))->toBeEmpty();
});

it('stops the walk at an orphaned parent pointer', function (): void {
    $repo = new InMemoryRoleRepository();
    $leaf = makeAncestorRole(
        '33333333-3333-3333-3333-333333333333',
        '99999999-9999-9999-9999-999999999999',
    );
    $repo->save($leaf);

    expect($repo->resolveAncestors($leaf->id))->toBeEmpty();
});
