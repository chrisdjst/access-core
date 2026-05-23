<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Module\CreateModule\CreateModule;
use ModularizeRbac\Core\Application\Module\CreateModule\CreateModuleInput;
use ModularizeRbac\Core\Application\Module\ListUserAccessibleModules\ListUserAccessibleModules;
use ModularizeRbac\Core\Application\Role\SyncRoleModules\SyncRoleModules;
use ModularizeRbac\Core\Application\Role\SyncRoleModules\SyncRoleModulesInput;
use ModularizeRbac\Core\Domain\Role\GuardName;
use ModularizeRbac\Core\Domain\Role\Role;
use ModularizeRbac\Core\Domain\Role\RoleLevel;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Tests\Application\Doubles\AllowingAuthorizer;
use ModularizeRbac\Core\Tests\Application\Doubles\FixedClock;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryExternalPermissionGateway;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryModuleRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryRoleModulePermissionRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryRoleRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryUserRoleResolver;
use ModularizeRbac\Core\Tests\Application\Doubles\PassthroughUnitOfWork;
use ModularizeRbac\Core\Tests\Application\Doubles\RecordingEventDispatcher;
use ModularizeRbac\Core\Tests\Application\Doubles\SequentialIdGenerator;

function accessStack(): array
{
    $modules = new InMemoryModuleRepository();
    $bindings = new InMemoryRoleModulePermissionRepository($modules);
    $roles = new InMemoryRoleRepository();
    $userRoles = new InMemoryUserRoleResolver();
    $external = new InMemoryExternalPermissionGateway();
    $auth = new AllowingAuthorizer();
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $ids = new SequentialIdGenerator();

    $create = new CreateModule($modules, $auth, new PassthroughUnitOfWork(), new RecordingEventDispatcher(), $ids, $clock);
    $sync = new SyncRoleModules(
        $roles, $modules, $bindings, $external, $auth,
        new PassthroughUnitOfWork(), new RecordingEventDispatcher(), $ids, $clock,
    );
    $useCase = new ListUserAccessibleModules(
        userRoles: $userRoles,
        bindings: $bindings,
        authorizer: $auth,
    );

    return compact('useCase', 'create', 'sync', 'roles', 'userRoles', 'clock');
}

function seedRole(InMemoryRoleRepository $repo, FixedClock $clock, string $id, string $name): Role
{
    $role = Role::create(
        id: new Uuid($id),
        name: $name,
        displayName: ucfirst($name),
        guard: new GuardName('admin'),
        tenantId: null,
        level: new RoleLevel(10),
        isSystem: false,
        clock: $clock,
    );
    $repo->save($role);

    return $role;
}

it('returns modules accessible through any of the user roles, distinct', function (): void {
    ['useCase' => $useCase, 'create' => $create, 'sync' => $sync, 'roles' => $roles, 'userRoles' => $userRoles, 'clock' => $clock] = accessStack();
    $events = $create->execute(new CreateModuleInput('events', 'Events', null, null, null, 50));
    $billing = $create->execute(new CreateModuleInput('billing', 'Billing', null, null, null, 100));
    $reports = $create->execute(new CreateModuleInput('reports', 'Reports', null, null, null, 75));

    $editor = seedRole($roles, $clock, '11111111-1111-1111-1111-111111111111', 'editor');
    $viewer = seedRole($roles, $clock, '22222222-2222-2222-2222-222222222222', 'viewer');

    $sync->execute(new SyncRoleModulesInput($editor->id->value, [
        ['module_id' => $events->id, 'is_writing_allowed' => true],
        ['module_id' => $billing->id, 'is_reading_allowed' => true],
    ]));
    $sync->execute(new SyncRoleModulesInput($viewer->id->value, [
        ['module_id' => $events->id, 'is_reading_allowed' => true], // dup of editor's events binding
        ['module_id' => $reports->id, 'is_reading_allowed' => true],
    ]));

    $userId = new Uuid('33333333-3333-3333-3333-333333333333');
    $userRoles->assign($userId, $editor->id, $viewer->id);

    $accessible = $useCase->execute($userId->value);

    expect(array_map(fn ($m) => $m->slug, $accessible))
        ->toBe(['events', 'reports', 'billing']); // sorted by sort_order ASC
});

it('excludes modules where all flags are false', function (): void {
    ['useCase' => $useCase, 'create' => $create, 'sync' => $sync, 'roles' => $roles, 'userRoles' => $userRoles, 'clock' => $clock] = accessStack();
    $events = $create->execute(new CreateModuleInput('events', 'Events', null, null, null));
    $billing = $create->execute(new CreateModuleInput('billing', 'Billing', null, null, null));

    $role = seedRole($roles, $clock, '11111111-1111-1111-1111-111111111111', 'editor');
    $sync->execute(new SyncRoleModulesInput($role->id->value, [
        ['module_id' => $events->id, 'is_reading_allowed' => true],
        ['module_id' => $billing->id], // no flags = all false
    ]));

    $userId = new Uuid('33333333-3333-3333-3333-333333333333');
    $userRoles->assign($userId, $role->id);

    $accessible = $useCase->execute($userId->value);

    expect(array_map(fn ($m) => $m->slug, $accessible))->toBe(['events']);
});

it('returns empty list for a user with no roles', function (): void {
    ['useCase' => $useCase] = accessStack();
    expect($useCase->execute('33333333-3333-3333-3333-333333333333'))->toBe([]);
});

it('returns empty list for a user whose roles have no bindings', function (): void {
    ['useCase' => $useCase, 'roles' => $roles, 'userRoles' => $userRoles, 'clock' => $clock] = accessStack();
    $role = seedRole($roles, $clock, '11111111-1111-1111-1111-111111111111', 'editor');
    $userId = new Uuid('33333333-3333-3333-3333-333333333333');
    $userRoles->assign($userId, $role->id);

    expect($useCase->execute($userId->value))->toBe([]);
});
