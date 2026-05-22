<?php

declare(strict_types=1);

use Modularize\Access\Application\Module\CreateModule\CreateModule;
use Modularize\Access\Application\Module\CreateModule\CreateModuleInput;
use Modularize\Access\Application\Module\DeleteModule\DeleteModule;
use Modularize\Access\Application\Module\ListModules\ListModules;
use Modularize\Access\Application\Module\ShowModule\ShowModule;
use Modularize\Access\Domain\Events\ModuleDeleted;
use Modularize\Access\Exceptions\NotFound;
use Modularize\Access\Tests\Application\Doubles\AllowingAuthorizer;
use Modularize\Access\Tests\Application\Doubles\FixedClock;
use Modularize\Access\Tests\Application\Doubles\InMemoryModuleRepository;
use Modularize\Access\Tests\Application\Doubles\PassthroughUnitOfWork;
use Modularize\Access\Tests\Application\Doubles\RecordingEventDispatcher;
use Modularize\Access\Tests\Application\Doubles\SequentialIdGenerator;

function moduleStack(): array
{
    $modules = new InMemoryModuleRepository();
    $auth = new AllowingAuthorizer();
    $events = new RecordingEventDispatcher();
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $ids = new SequentialIdGenerator();

    $create = new CreateModule($modules, $auth, new PassthroughUnitOfWork(), $events, $ids, $clock);
    $delete = new DeleteModule($modules, $auth, new PassthroughUnitOfWork(), $events, $clock);
    $list = new ListModules($modules, $auth);
    $show = new ShowModule($modules, $auth);

    return compact('create', 'delete', 'list', 'show', 'events');
}

it('soft-deletes a module and emits ModuleDeleted', function (): void {
    ['create' => $create, 'delete' => $delete, 'events' => $events, 'show' => $show, 'list' => $list] = moduleStack();
    $created = $create->execute(new CreateModuleInput('events', 'Events', null, null, null));
    $events->dispatched = [];

    $delete->execute($created->id);

    $emitted = $events->dispatched;
    expect($emitted)->toHaveCount(1)
        ->and($emitted[0])->toBeInstanceOf(ModuleDeleted::class)
        ->and($list->execute())->toBeEmpty();

    // Show still returns the deleted entity (callers can decide to 404)
    $reloaded = $show->execute($created->id);
    expect($reloaded->deletedAt)->not->toBeNull();
});

it('lists only non-deleted modules in tree order', function (): void {
    ['create' => $create, 'list' => $list] = moduleStack();
    $root = $create->execute(new CreateModuleInput('billing', 'Billing', null, null, null, 100));
    $child = $create->execute(new CreateModuleInput('billing.invoices', 'Invoices', null, null, $root->id, 10));
    $events = $create->execute(new CreateModuleInput('events', 'Events', null, null, null, 50));

    $result = $list->execute();
    expect(array_map(fn ($m) => $m->slug, $result))
        ->toBe(['events', 'billing', 'billing.invoices']);
});

it('show throws NotFound on missing id', function (): void {
    ['show' => $show] = moduleStack();
    expect(fn () => $show->execute('11111111-1111-1111-1111-111111111111'))->toThrow(NotFound::class);
});
