<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Module\CreateModule\CreateModule;
use ModularizeRbac\Core\Application\Module\CreateModule\CreateModuleInput;
use ModularizeRbac\Core\Application\Role\GetRolePermissionMatrix\GetRolePermissionMatrix;
use ModularizeRbac\Core\Application\Role\SyncRoleModules\SyncRoleModules;
use ModularizeRbac\Core\Application\Role\SyncRoleModules\SyncRoleModulesInput;
use ModularizeRbac\Core\Domain\Role\GuardName;
use ModularizeRbac\Core\Domain\Role\Role;
use ModularizeRbac\Core\Domain\Role\RoleLevel;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\AuthorizationFailed;
use ModularizeRbac\Core\Exceptions\NotFound;
use ModularizeRbac\Core\Tests\Application\Doubles\AllowingAuthorizer;
use ModularizeRbac\Core\Tests\Application\Doubles\FixedClock;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryExternalPermissionGateway;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryModuleRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryRoleModulePermissionRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryRoleRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\PassthroughUnitOfWork;
use ModularizeRbac\Core\Tests\Application\Doubles\RecordingEventDispatcher;
use ModularizeRbac\Core\Tests\Application\Doubles\SequentialIdGenerator;

function matrixStack(): array
{
    $modules = new InMemoryModuleRepository();
    $bindings = new InMemoryRoleModulePermissionRepository($modules);
    $roles = new InMemoryRoleRepository();
    $external = new InMemoryExternalPermissionGateway();
    $auth = new AllowingAuthorizer();
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $ids = new SequentialIdGenerator();

    $create = new CreateModule(
        modules: $modules,
        authorizer: $auth,
        uow: new PassthroughUnitOfWork(),
        events: new RecordingEventDispatcher(),
        ids: $ids,
        clock: $clock,
    );
    $sync = new SyncRoleModules(
        roles: $roles,
        modules: $modules,
        bindings: $bindings,
        external: $external,
        authorizer: $auth,
        uow: new PassthroughUnitOfWork(),
        events: new RecordingEventDispatcher(),
        ids: $ids,
        clock: $clock,
    );
    $matrix = new GetRolePermissionMatrix(
        roles: $roles,
        bindings: $bindings,
        authorizer: $auth,
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

    return compact('matrix', 'create', 'sync', 'role', 'auth');
}

it('returns role + matrix sorted by sort_order then slug', function (): void {
    ['matrix' => $matrix, 'create' => $create, 'sync' => $sync, 'role' => $role] = matrixStack();
    $billing = $create->execute(new CreateModuleInput('billing', 'Billing', null, null, null, 100));
    $events = $create->execute(new CreateModuleInput('events', 'Events', null, null, null, 50));

    $sync->execute(new SyncRoleModulesInput($role->id->value, [
        ['module_id' => $billing->id, 'is_reading_allowed' => true],
        ['module_id' => $events->id, 'is_reading_allowed' => true, 'is_writing_allowed' => true],
    ]));

    $out = $matrix->execute($role->id->value);

    expect($out->role->name)->toBe('editor')
        ->and($out->modules)->toHaveCount(2)
        ->and($out->modules[0]->slug)->toBe('events')
        ->and($out->modules[1]->slug)->toBe('billing')
        ->and($out->modules[0]->isReadingAllowed)->toBeTrue()
        ->and($out->modules[0]->isWritingAllowed)->toBeTrue()
        ->and($out->modules[0]->isDeleteAllowed)->toBeFalse();
});

it('returns empty matrix when role has no bindings', function (): void {
    ['matrix' => $matrix, 'role' => $role] = matrixStack();
    $out = $matrix->execute($role->id->value);

    expect($out->modules)->toBe([])
        ->and($out->role->name)->toBe('editor');
});

it('throws NotFound on missing role id', function (): void {
    ['matrix' => $matrix] = matrixStack();
    $matrix->execute('99999999-9999-9999-9999-999999999999');
})->throws(NotFound::class);

it('enforces admin.roles.view authorization', function (): void {
    ['matrix' => $matrix, 'role' => $role, 'auth' => $auth] = matrixStack();
    $auth->denyByDefault();
    $matrix->execute($role->id->value);
})->throws(AuthorizationFailed::class);
