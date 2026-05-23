<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Module\CreateModule\CreateModule;
use ModularizeRbac\Core\Application\Module\CreateModule\CreateModuleInput;
use ModularizeRbac\Core\Application\Role\SyncRoleModules\SyncRoleModules;
use ModularizeRbac\Core\Application\Role\SyncRoleModules\SyncRoleModulesInput;
use ModularizeRbac\Core\Domain\Events\RolePermissionsChanged;
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
use ModularizeRbac\Core\Tests\Application\Doubles\PassthroughUnitOfWork;
use ModularizeRbac\Core\Tests\Application\Doubles\RecordingEventDispatcher;
use ModularizeRbac\Core\Tests\Application\Doubles\SequentialIdGenerator;

function syncStack(): array
{
    $modules = new InMemoryModuleRepository();
    $roles = new InMemoryRoleRepository();
    $bindings = new InMemoryRoleModulePermissionRepository();
    $external = new InMemoryExternalPermissionGateway();
    $events = new RecordingEventDispatcher();
    $auth = new AllowingAuthorizer();
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $ids = new SequentialIdGenerator();

    $create = new CreateModule($modules, $auth, new PassthroughUnitOfWork(), new RecordingEventDispatcher(), $ids, $clock);
    $sync = new SyncRoleModules(
        roles: $roles,
        modules: $modules,
        bindings: $bindings,
        external: $external,
        authorizer: $auth,
        uow: new PassthroughUnitOfWork(),
        events: $events,
        ids: $ids,
        clock: $clock,
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

    return compact('sync', 'role', 'create', 'modules', 'roles', 'bindings', 'external', 'events');
}

it('grants the right permissions to a role with no prior bindings', function (): void {
    ['sync' => $sync, 'role' => $role, 'create' => $create, 'external' => $external, 'events' => $events] = syncStack();
    $events->dispatched = [];

    $events1 = $create->execute(new CreateModuleInput('events', 'Events', null, null, null));
    $billing = $create->execute(new CreateModuleInput('billing', 'Billing', null, null, null));

    $sync->execute(new SyncRoleModulesInput(
        roleId: $role->id->value,
        modules: [
            ['module_id' => $events1->id, 'is_reading_allowed' => true, 'is_writing_allowed' => true],
            ['module_id' => $billing->id, 'is_reading_allowed' => true],
        ],
    ));

    $held = $external->permissionsHeldBy($role->id, $role->guard());
    expect(array_map(fn ($p) => $p->value, $held))
        ->toContain('events.view', 'events.create', 'billing.view')
        ->and($events->dispatched)->toHaveCount(2);
    foreach ($events->dispatched as $ev) {
        expect($ev)->toBeInstanceOf(RolePermissionsChanged::class);
    }
});

it('revokes managed permissions that are no longer in the payload', function (): void {
    ['sync' => $sync, 'role' => $role, 'create' => $create, 'external' => $external] = syncStack();
    $events1 = $create->execute(new CreateModuleInput('events', 'Events', null, null, null));

    // First sync grants view+create
    $sync->execute(new SyncRoleModulesInput(
        roleId: $role->id->value,
        modules: [['module_id' => $events1->id, 'is_reading_allowed' => true, 'is_writing_allowed' => true]],
    ));
    // Second sync drops create
    $sync->execute(new SyncRoleModulesInput(
        roleId: $role->id->value,
        modules: [['module_id' => $events1->id, 'is_reading_allowed' => true]],
    ));

    $held = array_map(fn ($p) => $p->value, $external->permissionsHeldBy($role->id, $role->guard()));
    expect($held)->toContain('events.view')
        ->and($held)->not->toContain('events.create');
});

it('preserves non-managed permissions like manage/sign across syncs', function (): void {
    ['sync' => $sync, 'role' => $role, 'create' => $create, 'external' => $external] = syncStack();
    $events1 = $create->execute(new CreateModuleInput('events', 'Events', null, null, null));

    // Pre-seed an extra "events.manage" outside the managed set.
    $external->seed($role->id, $role->guard(), 'events.manage', 'events.sign');

    $sync->execute(new SyncRoleModulesInput(
        roleId: $role->id->value,
        modules: [['module_id' => $events1->id, 'is_reading_allowed' => true]],
    ));

    $held = array_map(fn ($p) => $p->value, $external->permissionsHeldBy($role->id, $role->guard()));
    expect($held)->toContain('events.view', 'events.manage', 'events.sign');
});

it('drops bindings absent from the payload and revokes ALL their permissions', function (): void {
    ['sync' => $sync, 'role' => $role, 'create' => $create, 'external' => $external, 'bindings' => $bindings] = syncStack();
    $events1 = $create->execute(new CreateModuleInput('events', 'Events', null, null, null));
    $billing = $create->execute(new CreateModuleInput('billing', 'Billing', null, null, null));

    // First sync binds both
    $sync->execute(new SyncRoleModulesInput(
        roleId: $role->id->value,
        modules: [
            ['module_id' => $events1->id, 'is_reading_allowed' => true],
            ['module_id' => $billing->id, 'is_reading_allowed' => true, 'is_writing_allowed' => true],
        ],
    ));

    // Pre-seed an extra non-managed permission on billing
    $external->applyPlan($role->id, $role->guard(), [new \ModularizeRbac\Core\Domain\Permission\PermissionName('billing.approve')], []);

    // Second sync drops billing entirely
    $sync->execute(new SyncRoleModulesInput(
        roleId: $role->id->value,
        modules: [['module_id' => $events1->id, 'is_reading_allowed' => true]],
    ));

    $held = array_map(fn ($p) => $p->value, $external->permissionsHeldBy($role->id, $role->guard()));
    expect($held)->toContain('events.view')
        ->and($held)->not->toContain('billing.view', 'billing.create', 'billing.approve');

    // Binding for billing is gone
    expect($bindings->findByRoleAndModule($role->id, new \ModularizeRbac\Core\Domain\Shared\Uuid($billing->id)))
        ->toBeNull();
});

it('is idempotent: re-syncing identical state emits no events', function (): void {
    ['sync' => $sync, 'role' => $role, 'create' => $create, 'events' => $events] = syncStack();
    $events1 = $create->execute(new CreateModuleInput('events', 'Events', null, null, null));

    $sync->execute(new SyncRoleModulesInput(
        roleId: $role->id->value,
        modules: [['module_id' => $events1->id, 'is_reading_allowed' => true]],
    ));
    $events->dispatched = [];

    $sync->execute(new SyncRoleModulesInput(
        roleId: $role->id->value,
        modules: [['module_id' => $events1->id, 'is_reading_allowed' => true]],
    ));

    expect($events->dispatched)->toBeEmpty();
});
