<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Role\ListRoles\ListRolesPaginated;
use ModularizeRbac\Core\Application\Role\RoleFilter;
use ModularizeRbac\Core\Application\Shared\Pagination;
use ModularizeRbac\Core\Domain\Role\GuardName;
use ModularizeRbac\Core\Domain\Role\Role;
use ModularizeRbac\Core\Domain\Role\RoleLevel;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Tests\Application\Doubles\AllowingAuthorizer;
use ModularizeRbac\Core\Tests\Application\Doubles\FixedClock;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryRoleRepository;

function seedFilteredRole(InMemoryRoleRepository $repo, string $name, string $guard = 'admin', ?string $tenantId = null, int $level = 0, bool $isSystem = false, ?string $parentId = null): Role
{
    static $counter = 0;
    $counter++;
    $hex = str_pad(dechex($counter), 32, '0', STR_PAD_LEFT);
    $uuid = sprintf('%s-%s-%s-%s-%s',
        substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4),
        substr($hex, 16, 4), substr($hex, 20, 12),
    );
    $role = Role::create(
        id: new Uuid($uuid),
        name: $name,
        displayName: null,
        guard: new GuardName($guard),
        tenantId: $tenantId !== null ? new Uuid($tenantId) : null,
        level: new RoleLevel($level),
        isSystem: $isSystem,
        clock: FixedClock::at('2026-01-01T00:00:00Z'),
        parentRoleId: $parentId !== null ? new Uuid($parentId) : null,
    );
    $repo->save($role);

    return $role;
}

function paginatedRoleStack(): array
{
    $roles = new InMemoryRoleRepository();
    $auth = new AllowingAuthorizer();
    $list = new ListRolesPaginated($roles, $auth);

    return compact('list', 'roles');
}

it('returns the windowed slice + total count', function (): void {
    ['list' => $list, 'roles' => $roles] = paginatedRoleStack();
    for ($i = 0; $i < 15; $i++) {
        seedFilteredRole($roles, 'role'.$i, level: $i);
    }

    $result = $list->execute(new RoleFilter(), new Pagination(limit: 5, offset: 0));

    expect($result->items)->toHaveCount(5)
        ->and($result->total)->toBe(15);
});

it('filters by guard', function (): void {
    ['list' => $list, 'roles' => $roles] = paginatedRoleStack();
    seedFilteredRole($roles, 'admin1', guard: 'admin');
    seedFilteredRole($roles, 'web1', guard: 'web');

    $result = $list->execute(new RoleFilter(guard: 'web'), Pagination::default());

    expect(count($result->items))->toBe(1)
        ->and($result->items[0]->name)->toBe('web1');
});

it('filters by is_system', function (): void {
    ['list' => $list, 'roles' => $roles] = paginatedRoleStack();
    seedFilteredRole($roles, 'sys', isSystem: true);
    seedFilteredRole($roles, 'normal', isSystem: false);

    $result = $list->execute(new RoleFilter(isSystem: true), Pagination::default());

    expect(count($result->items))->toBe(1)
        ->and($result->items[0]->name)->toBe('sys');
});

it('filters by level_min and level_max', function (): void {
    ['list' => $list, 'roles' => $roles] = paginatedRoleStack();
    seedFilteredRole($roles, 'low', level: 1);
    seedFilteredRole($roles, 'mid', level: 50);
    seedFilteredRole($roles, 'hi', level: 100);

    $result = $list->execute(new RoleFilter(levelMin: 10, levelMax: 90), Pagination::default());

    expect(count($result->items))->toBe(1)
        ->and($result->items[0]->name)->toBe('mid');
});

it('filters by has_parent', function (): void {
    ['list' => $list, 'roles' => $roles] = paginatedRoleStack();
    $parent = seedFilteredRole($roles, 'parent1');
    seedFilteredRole($roles, 'child1', parentId: $parent->id->value);

    $roots = $list->execute(new RoleFilter(hasParent: false), Pagination::default());
    $children = $list->execute(new RoleFilter(hasParent: true), Pagination::default());

    expect(count($roots->items))->toBe(1)
        ->and($roots->items[0]->name)->toBe('parent1')
        ->and(count($children->items))->toBe(1)
        ->and($children->items[0]->name)->toBe('child1');
});

it('rejects level_min greater than level_max at the input layer', function (): void {
    expect(fn () => new RoleFilter(levelMin: 50, levelMax: 10))->toThrow(InvalidInput::class);
});
