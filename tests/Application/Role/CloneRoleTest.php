<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Module\CreateModule\CreateModule;
use ModularizeRbac\Core\Application\Module\CreateModule\CreateModuleInput;
use ModularizeRbac\Core\Application\Role\CloneRole\CloneRole;
use ModularizeRbac\Core\Application\Role\CloneRole\CloneRoleInput;
use ModularizeRbac\Core\Application\Role\SyncRoleModules\SyncRoleModules;
use ModularizeRbac\Core\Application\Role\SyncRoleModules\SyncRoleModulesInput;
use ModularizeRbac\Core\Domain\Events\RolePermissionsChanged;
use ModularizeRbac\Core\Domain\Role\GuardName;
use ModularizeRbac\Core\Domain\Role\Role;
use ModularizeRbac\Core\Domain\Role\RoleLevel;
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

function cloneStack(): array
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
        events: new RecordingEventDispatcher(),
        ids: $ids,
        clock: $clock,
    );
    $clone = new CloneRole(
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

    $source = Role::create(
        id: new Uuid('11111111-1111-1111-1111-111111111111'),
        name: 'editor',
        displayName: 'Editor',
        guard: new GuardName('admin'),
        tenantId: null,
        level: new RoleLevel(50),
        isSystem: false,
        clock: $clock,
    );
    $roles->save($source);

    return compact('clone', 'sync', 'create', 'source', 'modules', 'roles', 'bindings', 'external', 'events');
}

it('creates a new role with the source guard/tenant/level and the requested name', function (): void {
    ['clone' => $clone, 'source' => $source, 'roles' => $roles] = cloneStack();

    $output = $clone->execute(new CloneRoleInput(
        sourceRoleId: $source->id->value,
        name: 'editor_v2',
        displayName: 'Editor v2',
    ));

    expect($output->name)->toBe('editor_v2')
        ->and($output->displayName)->toBe('Editor v2')
        ->and($output->guard)->toBe('admin')
        ->and($output->tenantId)->toBeNull()
        ->and($output->level)->toBe(50)
        ->and($output->isSystem)->toBeFalse()
        ->and($output->id)->not->toBe($source->id->value);

    expect($roles->find(new Uuid($output->id)))->not->toBeNull();
});

it('falls back to the source displayName when none is provided', function (): void {
    ['clone' => $clone, 'source' => $source] = cloneStack();

    $output = $clone->execute(new CloneRoleInput(
        sourceRoleId: $source->id->value,
        name: 'editor_copy',
    ));

    expect($output->displayName)->toBe('Editor');
});

it('never marks a clone as a system role even if the source is', function (): void {
    ['clone' => $clone, 'roles' => $roles] = cloneStack();
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $systemRole = Role::create(
        id: new Uuid('22222222-2222-2222-2222-222222222222'),
        name: 'super_admin',
        displayName: 'Super',
        guard: new GuardName('admin'),
        tenantId: null,
        level: new RoleLevel(99),
        isSystem: true,
        clock: $clock,
    );
    $roles->save($systemRole);

    $output = $clone->execute(new CloneRoleInput(
        sourceRoleId: $systemRole->id->value,
        name: 'super_admin_copy',
    ));

    expect($output->isSystem)->toBeFalse();
});

it('copies bindings: each source binding becomes a fresh ModulePermission + RoleModulePermission for the clone', function (): void {
    ['clone' => $clone, 'sync' => $sync, 'create' => $create, 'source' => $source, 'bindings' => $bindings] = cloneStack();

    $events = $create->execute(new CreateModuleInput('events', 'Events', null, null, null));
    $billing = $create->execute(new CreateModuleInput('billing', 'Billing', null, null, null));

    $sync->execute(new SyncRoleModulesInput(
        roleId: $source->id->value,
        modules: [
            ['module_id' => $events->id, 'is_reading_allowed' => true, 'is_writing_allowed' => true],
            ['module_id' => $billing->id, 'is_reading_allowed' => true],
        ],
    ));

    $output = $clone->execute(new CloneRoleInput(
        sourceRoleId: $source->id->value,
        name: 'editor_v2',
    ));

    $cloneRows = $bindings->forRole(new Uuid($output->id));
    expect($cloneRows)->toHaveCount(2);

    $sourceRows = $bindings->forRole($source->id);
    // Fresh ids — no binding row is shared between source and clone
    $sourceBindingIds = array_map(fn ($r) => $r['binding']->id->value, $sourceRows);
    foreach ($cloneRows as $row) {
        expect($sourceBindingIds)->not->toContain($row['binding']->id->value);
    }
    $sourcePermissionIds = array_map(fn ($r) => $r['permission']->id->value, $sourceRows);
    foreach ($cloneRows as $row) {
        expect($sourcePermissionIds)->not->toContain($row['permission']->id->value);
    }
});

it('mirrors permissions into the external gateway for the clone', function (): void {
    ['clone' => $clone, 'sync' => $sync, 'create' => $create, 'source' => $source, 'external' => $external] = cloneStack();

    $events = $create->execute(new CreateModuleInput('events', 'Events', null, null, null));
    $sync->execute(new SyncRoleModulesInput(
        roleId: $source->id->value,
        modules: [['module_id' => $events->id, 'is_reading_allowed' => true, 'is_writing_allowed' => true]],
    ));

    $output = $clone->execute(new CloneRoleInput(
        sourceRoleId: $source->id->value,
        name: 'editor_v2',
    ));

    $held = array_map(fn ($p) => $p->value, $external->permissionsHeldBy(new Uuid($output->id), new GuardName('admin')));
    expect($held)->toContain('events.view', 'events.create');

    // Source role keeps its permissions intact
    $sourceHeld = array_map(fn ($p) => $p->value, $external->permissionsHeldBy($source->id, new GuardName('admin')));
    expect($sourceHeld)->toContain('events.view', 'events.create');
});

it('emits one RolePermissionsChanged event per cloned binding with grants only', function (): void {
    ['clone' => $clone, 'sync' => $sync, 'create' => $create, 'source' => $source, 'events' => $events] = cloneStack();

    $eventsMod = $create->execute(new CreateModuleInput('events', 'Events', null, null, null));
    $billing = $create->execute(new CreateModuleInput('billing', 'Billing', null, null, null));

    $sync->execute(new SyncRoleModulesInput(
        roleId: $source->id->value,
        modules: [
            ['module_id' => $eventsMod->id, 'is_reading_allowed' => true],
            ['module_id' => $billing->id, 'is_reading_allowed' => true],
        ],
    ));
    $events->dispatched = [];

    $clone->execute(new CloneRoleInput(
        sourceRoleId: $source->id->value,
        name: 'editor_v2',
    ));

    expect($events->dispatched)->toHaveCount(2);
    foreach ($events->dispatched as $ev) {
        expect($ev)->toBeInstanceOf(RolePermissionsChanged::class)
            ->and($ev->revoked)->toBeEmpty()
            ->and($ev->granted)->not->toBeEmpty();
    }
});

it('produces an empty matrix when the source has no bindings', function (): void {
    ['clone' => $clone, 'source' => $source, 'bindings' => $bindings, 'events' => $events] = cloneStack();

    $output = $clone->execute(new CloneRoleInput(
        sourceRoleId: $source->id->value,
        name: 'editor_v2',
    ));

    expect($bindings->forRole(new Uuid($output->id)))->toBeEmpty()
        ->and($events->dispatched)->toBeEmpty();
});

it('rejects a name that already exists for the same (guard, tenant)', function (): void {
    ['clone' => $clone, 'source' => $source, 'roles' => $roles] = cloneStack();
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $existing = Role::create(
        id: new Uuid('33333333-3333-3333-3333-333333333333'),
        name: 'editor_v2',
        displayName: 'Existing',
        guard: new GuardName('admin'),
        tenantId: null,
        level: new RoleLevel(10),
        isSystem: false,
        clock: $clock,
    );
    $roles->save($existing);

    expect(fn () => $clone->execute(new CloneRoleInput(
        sourceRoleId: $source->id->value,
        name: 'editor_v2',
    )))->toThrow(InvalidInput::class);
});

it('throws NotFound when the source role does not exist', function (): void {
    ['clone' => $clone] = cloneStack();

    expect(fn () => $clone->execute(new CloneRoleInput(
        sourceRoleId: '99999999-9999-9999-9999-999999999999',
        name: 'whatever',
    )))->toThrow(NotFound::class);
});

it('rejects a name that does not match the snake/kebab format at the input layer', function (): void {
    expect(fn () => new CloneRoleInput(
        sourceRoleId: '11111111-1111-1111-1111-111111111111',
        name: 'Bad Name With Spaces',
    ))->toThrow(InvalidInput::class);
});
