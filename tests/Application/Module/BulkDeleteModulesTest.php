<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Module\BulkDeleteModules\BulkDeleteModules;
use ModularizeRbac\Core\Application\Module\BulkDeleteModules\BulkDeleteModulesInput;
use ModularizeRbac\Core\Application\Module\CreateModule\CreateModule;
use ModularizeRbac\Core\Application\Module\CreateModule\CreateModuleInput;
use ModularizeRbac\Core\Domain\Events\ModuleDeleted;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Exceptions\NotFound;
use ModularizeRbac\Core\Tests\Application\Doubles\AllowingAuthorizer;
use ModularizeRbac\Core\Tests\Application\Doubles\FixedClock;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryModuleRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\PassthroughUnitOfWork;
use ModularizeRbac\Core\Tests\Application\Doubles\RecordingEventDispatcher;
use ModularizeRbac\Core\Tests\Application\Doubles\SequentialIdGenerator;

function bulkDeleteStack(): array
{
    $modules = new InMemoryModuleRepository();
    $events = new RecordingEventDispatcher();
    $auth = new AllowingAuthorizer();
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $ids = new SequentialIdGenerator();

    $create = new CreateModule($modules, $auth, new PassthroughUnitOfWork(), new RecordingEventDispatcher(), $ids, $clock);
    $bulkDelete = new BulkDeleteModules(
        modules: $modules,
        authorizer: $auth,
        uow: new PassthroughUnitOfWork(),
        events: $events,
        clock: $clock,
    );

    return compact('create', 'bulkDelete', 'modules', 'events');
}

it('soft-deletes every module in the payload and emits one ModuleDeleted per entry', function (): void {
    ['create' => $create, 'bulkDelete' => $bulkDelete, 'modules' => $modules, 'events' => $events] = bulkDeleteStack();

    $a = $create->execute(new CreateModuleInput('events', 'Events', null, null, null));
    $b = $create->execute(new CreateModuleInput('billing', 'Billing', null, null, null));
    $events->dispatched = [];

    $bulkDelete->execute(new BulkDeleteModulesInput([$a->id, $b->id]));

    expect($modules->find(new Uuid($a->id))->isDeleted())->toBeTrue()
        ->and($modules->find(new Uuid($b->id))->isDeleted())->toBeTrue()
        ->and($events->dispatched)->toHaveCount(2);
    foreach ($events->dispatched as $ev) {
        expect($ev)->toBeInstanceOf(ModuleDeleted::class);
    }
});

it('rolls back the whole batch when one id is missing', function (): void {
    ['create' => $create, 'bulkDelete' => $bulkDelete, 'modules' => $modules, 'events' => $events] = bulkDeleteStack();

    $a = $create->execute(new CreateModuleInput('events', 'Events', null, null, null));
    $events->dispatched = [];

    expect(fn () => $bulkDelete->execute(new BulkDeleteModulesInput([
        $a->id,
        '99999999-9999-9999-9999-999999999999',
    ])))->toThrow(NotFound::class);

    expect($modules->find(new Uuid($a->id))->isDeleted())->toBeFalse()
        ->and($events->dispatched)->toBeEmpty();
});

it('rejects an empty payload at the input layer', function (): void {
    expect(fn () => new BulkDeleteModulesInput([]))->toThrow(InvalidInput::class);
});

it('rejects duplicate ids within the same payload', function (): void {
    expect(fn () => new BulkDeleteModulesInput([
        '11111111-1111-1111-1111-111111111111',
        '11111111-1111-1111-1111-111111111111',
    ]))->toThrow(InvalidInput::class);
});

it('rejects malformed UUIDs at the input layer', function (): void {
    expect(fn () => new BulkDeleteModulesInput(['not-a-uuid']))->toThrow(InvalidInput::class);
});
