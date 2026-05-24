<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Module\BulkCreateModules\BulkCreateModules;
use ModularizeRbac\Core\Application\Module\BulkCreateModules\BulkCreateModulesInput;
use ModularizeRbac\Core\Application\Module\CreateModule\CreateModule;
use ModularizeRbac\Core\Application\Module\CreateModule\CreateModuleInput;
use ModularizeRbac\Core\Domain\Events\ModuleCreated;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Tests\Application\Doubles\AllowingAuthorizer;
use ModularizeRbac\Core\Tests\Application\Doubles\FixedClock;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryModuleRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\PassthroughUnitOfWork;
use ModularizeRbac\Core\Tests\Application\Doubles\RecordingEventDispatcher;
use ModularizeRbac\Core\Tests\Application\Doubles\SequentialIdGenerator;

function bulkCreateStack(): array
{
    $modules = new InMemoryModuleRepository();
    $events = new RecordingEventDispatcher();
    $auth = new AllowingAuthorizer();
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $ids = new SequentialIdGenerator();

    $bulkCreate = new BulkCreateModules(
        modules: $modules,
        authorizer: $auth,
        uow: new PassthroughUnitOfWork(),
        events: $events,
        ids: $ids,
        clock: $clock,
    );

    return compact('bulkCreate', 'modules', 'events');
}

it('creates every entry in the payload and emits one ModuleCreated event each', function (): void {
    ['bulkCreate' => $bulkCreate, 'modules' => $modules, 'events' => $events] = bulkCreateStack();

    $outputs = $bulkCreate->execute(new BulkCreateModulesInput([
        ['slug' => 'events', 'name' => 'Events'],
        ['slug' => 'billing', 'name' => 'Billing', 'sort_order' => 10],
        ['slug' => 'reports', 'name' => 'Reports', 'icon' => 'chart'],
    ]));

    expect($outputs)->toHaveCount(3)
        ->and($outputs[0]->slug)->toBe('events')
        ->and($outputs[1]->slug)->toBe('billing')
        ->and($outputs[1]->sortOrder)->toBe(10)
        ->and($outputs[2]->icon)->toBe('chart');

    $slugs = array_map(fn ($m) => $m->slug()->value, $modules->allActiveTree());
    expect($slugs)->toContain('events', 'billing', 'reports');

    expect($events->dispatched)->toHaveCount(3);
    foreach ($events->dispatched as $ev) {
        expect($ev)->toBeInstanceOf(ModuleCreated::class);
    }
});

it('rolls back the whole batch when one slug already exists', function (): void {
    ['bulkCreate' => $bulkCreate, 'modules' => $modules, 'events' => $events] = bulkCreateStack();
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $ids = new SequentialIdGenerator();
    $create = new CreateModule($modules, new AllowingAuthorizer(), new PassthroughUnitOfWork(), new RecordingEventDispatcher(), $ids, $clock);
    $create->execute(new CreateModuleInput('events', 'Events', null, null, null));

    $events->dispatched = [];

    expect(fn () => $bulkCreate->execute(new BulkCreateModulesInput([
        ['slug' => 'billing', 'name' => 'Billing'],
        ['slug' => 'events', 'name' => 'Duplicate'],
        ['slug' => 'reports', 'name' => 'Reports'],
    ])))->toThrow(InvalidInput::class);

    $slugs = array_map(fn ($m) => $m->slug()->value, $modules->allActiveTree());
    expect($slugs)->toBe(['events'])
        ->and($events->dispatched)->toBeEmpty();
});

it('rejects duplicate slugs within the same payload', function (): void {
    ['bulkCreate' => $bulkCreate] = bulkCreateStack();

    expect(fn () => $bulkCreate->execute(new BulkCreateModulesInput([
        ['slug' => 'events', 'name' => 'Events'],
        ['slug' => 'events', 'name' => 'Events Again'],
    ])))->toThrow(InvalidInput::class);
});

it('rejects entries that reference a missing parent module', function (): void {
    ['bulkCreate' => $bulkCreate] = bulkCreateStack();

    expect(fn () => $bulkCreate->execute(new BulkCreateModulesInput([
        [
            'slug' => 'events.sub',
            'name' => 'Sub',
            'root_module_id' => '99999999-9999-9999-9999-999999999999',
        ],
    ])))->toThrow(InvalidInput::class);
});

it('rejects an empty payload at the input layer', function (): void {
    expect(fn () => new BulkCreateModulesInput([]))->toThrow(InvalidInput::class);
});

it('rejects entries missing required keys at the input layer', function (): void {
    expect(fn () => new BulkCreateModulesInput([['slug' => 'events']]))->toThrow(InvalidInput::class);
});
