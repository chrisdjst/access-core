<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Module\CreateModule\CreateModule;
use ModularizeRbac\Core\Application\Module\CreateModule\CreateModuleInput;
use ModularizeRbac\Core\Application\Module\UpdateModule\UpdateModule;
use ModularizeRbac\Core\Application\Module\UpdateModule\UpdateModuleInput;
use ModularizeRbac\Core\Domain\Events\ModuleUpdated;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Exceptions\NotFound;
use ModularizeRbac\Core\Tests\Application\Doubles\AllowingAuthorizer;
use ModularizeRbac\Core\Tests\Application\Doubles\FixedClock;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryModuleRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\PassthroughUnitOfWork;
use ModularizeRbac\Core\Tests\Application\Doubles\RecordingEventDispatcher;
use ModularizeRbac\Core\Tests\Application\Doubles\SequentialIdGenerator;

function setupUpdate(): array
{
    $modules = new InMemoryModuleRepository();
    $auth = new AllowingAuthorizer();
    $events = new RecordingEventDispatcher();
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $ids = new SequentialIdGenerator();

    $create = new CreateModule(
        modules: $modules,
        authorizer: $auth,
        uow: new PassthroughUnitOfWork(),
        events: $events,
        ids: $ids,
        clock: $clock,
    );
    $update = new UpdateModule(
        modules: $modules,
        authorizer: $auth,
        uow: new PassthroughUnitOfWork(),
        events: $events,
        clock: $clock,
    );

    return compact('create', 'update', 'modules', 'auth', 'events', 'clock');
}

it('updates a module and emits ModuleUpdated', function (): void {
    ['create' => $create, 'update' => $update, 'events' => $events, 'clock' => $clock] = setupUpdate();
    $created = $create->execute(new CreateModuleInput('events', 'Events', null, null, null, 5));
    $events->dispatched = []; // forget the create event

    $clock->tick('+1 hour');
    $out = $update->execute(new UpdateModuleInput(
        id: $created->id,
        name: 'Events v2',
        redirect: '/events2',
        icon: 'calendar2',
        rootModuleId: null,
        sortOrder: 10,
        isActive: true,
    ));

    expect($out->name)->toBe('Events v2')
        ->and($out->sortOrder)->toBe(10)
        ->and($events->dispatched)->toHaveCount(1)
        ->and($events->dispatched[0])->toBeInstanceOf(ModuleUpdated::class);
});

it('emits no event when nothing changed', function (): void {
    ['create' => $create, 'update' => $update, 'events' => $events] = setupUpdate();
    $created = $create->execute(new CreateModuleInput('events', 'Events', '/r', 'i', null, 5, true));
    $events->dispatched = [];

    $update->execute(new UpdateModuleInput(
        id: $created->id,
        name: 'Events',
        redirect: '/r',
        icon: 'i',
        rootModuleId: null,
        sortOrder: 5,
        isActive: true,
    ));

    expect($events->dispatched)->toBeEmpty();
});

it('rejects self-parenting', function (): void {
    ['create' => $create, 'update' => $update] = setupUpdate();
    $created = $create->execute(new CreateModuleInput('events', 'Events', null, null, null));

    expect(fn () => $update->execute(new UpdateModuleInput(
        id: $created->id,
        name: 'Events',
        redirect: null,
        icon: null,
        rootModuleId: $created->id,
        sortOrder: 0,
        isActive: true,
    )))->toThrow(InvalidInput::class, 'A module cannot be its own parent.');
});

it('rejects unknown id', function (): void {
    ['update' => $update] = setupUpdate();

    expect(fn () => $update->execute(new UpdateModuleInput(
        id: 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
        name: 'X',
        redirect: null,
        icon: null,
        rootModuleId: null,
        sortOrder: 0,
        isActive: true,
    )))->toThrow(NotFound::class);
});

it('rejects nesting deeper than one level', function (): void {
    ['create' => $create, 'update' => $update] = setupUpdate();
    $root = $create->execute(new CreateModuleInput('billing', 'Billing', null, null, null));
    $mid = $create->execute(new CreateModuleInput('billing.invoices', 'Invoices', null, null, $root->id));
    $leaf = $create->execute(new CreateModuleInput('billing.invoices.draft', 'Draft', null, null, null));

    expect(fn () => $update->execute(new UpdateModuleInput(
        id: $leaf->id,
        name: 'Draft',
        redirect: null,
        icon: null,
        rootModuleId: $mid->id,
        sortOrder: 0,
        isActive: true,
    )))->toThrow(InvalidInput::class, 'one level');
});
