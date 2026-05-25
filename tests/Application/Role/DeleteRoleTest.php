<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Role\CreateRole\CreateRole;
use ModularizeRbac\Core\Application\Role\CreateRole\CreateRoleInput;
use ModularizeRbac\Core\Application\Role\DeleteRole\DeleteRole;
use ModularizeRbac\Core\Application\Role\SyncRoleModules\SyncRoleModules;
use ModularizeRbac\Core\Application\Role\SyncRoleModules\SyncRoleModulesInput;
use ModularizeRbac\Core\Application\Module\CreateModule\CreateModule;
use ModularizeRbac\Core\Application\Module\CreateModule\CreateModuleInput;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;
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

function deleteRoleStack(): array
{
    $modules = new InMemoryModuleRepository();
    $bindings = new InMemoryRoleModulePermissionRepository($modules);
    $roles = new InMemoryRoleRepository();
    $external = new InMemoryExternalPermissionGateway();
    $auth = new AllowingAuthorizer();
    $clock = FixedClock::at('2026-06-01T00:00:00Z');
    $ids = new SequentialIdGenerator();
    $uow = new PassthroughUnitOfWork();

    $createRole = new CreateRole($roles, $auth, $uow, $ids, $clock);
    $createModule = new CreateModule($modules, $auth, $uow, new RecordingEventDispatcher(), $ids, $clock);
    $sync = new SyncRoleModules(
        $roles, $modules, $bindings, $external, $auth,
        $uow, new RecordingEventDispatcher(), $ids, $clock,
    );
    $delete = new DeleteRole($roles, $bindings, $auth, $uow, $clock);

    return compact('delete', 'createRole', 'createModule', 'sync', 'roles');
}

it('deletes a role that has no bindings', function (): void {
    ['delete' => $delete, 'createRole' => $createRole, 'roles' => $roles] = deleteRoleStack();
    $role = $createRole->execute(new CreateRoleInput('editor', null, 'admin', null));

    $delete->execute($role->id);

    expect($roles->find(new Uuid($role->id)))->toBeNull();
});

it('throws NotFound on a missing role id', function (): void {
    ['delete' => $delete] = deleteRoleStack();
    $delete->execute('99999999-9999-9999-9999-999999999999');
})->throws(NotFound::class);

it('refuses to delete a system role', function (): void {
    ['delete' => $delete, 'createRole' => $createRole] = deleteRoleStack();
    $role = $createRole->execute(new CreateRoleInput('super-admin', null, 'admin', null, level: 100, isSystem: true));

    expect(fn () => $delete->execute($role->id))->toThrow(InvalidInput::class, 'System roles cannot be deleted.');
});

it('refuses to delete a role that still has bindings', function (): void {
    ['delete' => $delete, 'createRole' => $createRole, 'createModule' => $createModule, 'sync' => $sync] = deleteRoleStack();
    $role = $createRole->execute(new CreateRoleInput('editor', null, 'admin', null));
    $module = $createModule->execute(new CreateModuleInput('events', 'Events', null, null, null));

    $sync->execute(new SyncRoleModulesInput($role->id, [
        ['module_id' => $module->id, 'is_reading_allowed' => true],
    ]));

    expect(fn () => $delete->execute($role->id))->toThrow(InvalidInput::class, 'still has module-permission bindings');
});

it('allows deletion once bindings are explicitly dropped via SyncRoleModules', function (): void {
    ['delete' => $delete, 'createRole' => $createRole, 'createModule' => $createModule, 'sync' => $sync, 'roles' => $roles] = deleteRoleStack();
    $role = $createRole->execute(new CreateRoleInput('editor', null, 'admin', null));
    $module = $createModule->execute(new CreateModuleInput('events', 'Events', null, null, null));

    $sync->execute(new SyncRoleModulesInput($role->id, [['module_id' => $module->id, 'is_reading_allowed' => true]]));
    $sync->execute(new SyncRoleModulesInput($role->id, [])); // drop bindings

    $delete->execute($role->id);

    expect($roles->find(new Uuid($role->id)))->toBeNull();
});
