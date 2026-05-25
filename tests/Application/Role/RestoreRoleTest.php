<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Module\CreateModule\CreateModule;
use ModularizeRbac\Core\Application\Module\CreateModule\CreateModuleInput;
use ModularizeRbac\Core\Application\Role\CreateRole\CreateRole;
use ModularizeRbac\Core\Application\Role\CreateRole\CreateRoleInput;
use ModularizeRbac\Core\Application\Role\DeleteRole\DeleteRole;
use ModularizeRbac\Core\Application\Role\RestoreRole\RestoreRole;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Exceptions\NotFound;
use ModularizeRbac\Core\Tests\Application\Doubles\AllowingAuthorizer;
use ModularizeRbac\Core\Tests\Application\Doubles\FixedClock;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryModuleRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryRoleModulePermissionRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryRoleRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\PassthroughUnitOfWork;
use ModularizeRbac\Core\Tests\Application\Doubles\RecordingEventDispatcher;
use ModularizeRbac\Core\Tests\Application\Doubles\SequentialIdGenerator;

function restoreRoleStack(): array
{
    $roles = new InMemoryRoleRepository();
    $modules = new InMemoryModuleRepository();
    $bindings = new InMemoryRoleModulePermissionRepository();
    $auth = new AllowingAuthorizer();
    $uow = new PassthroughUnitOfWork();
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $ids = new SequentialIdGenerator();

    $createRole = new CreateRole($roles, $auth, $uow, $ids, $clock);
    $delete = new DeleteRole($roles, $bindings, $auth, $uow, $clock);
    $restore = new RestoreRole($roles, $auth, $uow, $clock);

    return compact('restore', 'delete', 'createRole', 'roles');
}

it('soft-deletes by setting deletedAt and keeps the row reachable via findIncludingTrashed', function (): void {
    ['delete' => $delete, 'createRole' => $createRole, 'roles' => $roles] = restoreRoleStack();

    $role = $createRole->execute(new CreateRoleInput('editor', null, 'admin', null));
    $delete->execute($role->id);

    expect($roles->find(new Uuid($role->id)))->toBeNull()
        ->and($roles->findIncludingTrashed(new Uuid($role->id)))->not->toBeNull()
        ->and($roles->findIncludingTrashed(new Uuid($role->id))->isDeleted())->toBeTrue();
});

it('restore reverses the soft delete and the role becomes findable again', function (): void {
    ['delete' => $delete, 'restore' => $restore, 'createRole' => $createRole, 'roles' => $roles] = restoreRoleStack();

    $role = $createRole->execute(new CreateRoleInput('editor', null, 'admin', null));
    $delete->execute($role->id);
    $output = $restore->execute($role->id);

    expect($output->deletedAt)->toBeNull()
        ->and($roles->find(new Uuid($role->id)))->not->toBeNull()
        ->and($roles->find(new Uuid($role->id))->isDeleted())->toBeFalse();
});

it('restore throws NotFound for an unknown id', function (): void {
    ['restore' => $restore] = restoreRoleStack();

    expect(fn () => $restore->execute('99999999-9999-9999-9999-999999999999'))
        ->toThrow(NotFound::class);
});

it('restore throws InvalidInput when the role is not soft-deleted', function (): void {
    ['restore' => $restore, 'createRole' => $createRole] = restoreRoleStack();

    $role = $createRole->execute(new CreateRoleInput('editor', null, 'admin', null));

    expect(fn () => $restore->execute($role->id))->toThrow(InvalidInput::class);
});

it('search no longer returns soft-deleted roles', function (): void {
    ['delete' => $delete, 'createRole' => $createRole, 'roles' => $roles] = restoreRoleStack();

    $r1 = $createRole->execute(new CreateRoleInput('keep', null, 'admin', null));
    $r2 = $createRole->execute(new CreateRoleInput('trashed', null, 'admin', null));
    $delete->execute($r2->id);

    $listed = $roles->search(null, null);
    $names = array_map(fn ($r) => $r->name(), $listed);

    expect($names)->toContain('keep')
        ->and($names)->not->toContain('trashed');
});
