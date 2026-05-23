<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Module\CreateModule\CreateModule;
use ModularizeRbac\Core\Application\Module\CreateModule\CreateModuleInput;
use ModularizeRbac\Core\Domain\Events\ModuleCreated;
use ModularizeRbac\Core\Exceptions\AuthorizationFailed;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Tests\Application\Doubles\AllowingAuthorizer;
use ModularizeRbac\Core\Tests\Application\Doubles\FixedClock;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryModuleRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\PassthroughUnitOfWork;
use ModularizeRbac\Core\Tests\Application\Doubles\RecordingEventDispatcher;
use ModularizeRbac\Core\Tests\Application\Doubles\SequentialIdGenerator;

function makeCreateModuleUseCase(
    ?InMemoryModuleRepository $modules = null,
    ?AllowingAuthorizer $auth = null,
    ?RecordingEventDispatcher $events = null,
): array {
    $modules ??= new InMemoryModuleRepository();
    $auth ??= new AllowingAuthorizer();
    $events ??= new RecordingEventDispatcher();
    $useCase = new CreateModule(
        modules: $modules,
        authorizer: $auth,
        uow: new PassthroughUnitOfWork(),
        events: $events,
        ids: new SequentialIdGenerator(),
        clock: FixedClock::at('2026-01-01T00:00:00Z'),
    );

    return ['uc' => $useCase, 'modules' => $modules, 'auth' => $auth, 'events' => $events];
}

it('creates a module and emits ModuleCreated', function (): void {
    ['uc' => $uc, 'events' => $events, 'modules' => $modules] = makeCreateModuleUseCase();

    $out = $uc->execute(new CreateModuleInput(
        slug: 'events',
        name: 'Events',
        redirect: '/events',
        icon: 'calendar',
        rootModuleId: null,
        sortOrder: 10,
    ));

    expect($out->slug)->toBe('events')
        ->and($out->name)->toBe('Events')
        ->and($out->id)->toBe('00000000-0000-0000-0000-000000000001')
        ->and($events->dispatched)->toHaveCount(1)
        ->and($events->dispatched[0])->toBeInstanceOf(ModuleCreated::class)
        ->and($modules->find(new ModularizeRbac\Core\Domain\Shared\Uuid($out->id)))->not->toBeNull();
});

it('rejects a duplicate slug', function (): void {
    ['uc' => $uc] = makeCreateModuleUseCase();
    $uc->execute(new CreateModuleInput('events', 'Events', null, null, null));

    expect(fn () => $uc->execute(new CreateModuleInput('events', 'Events 2', null, null, null)))
        ->toThrow(InvalidInput::class);
});

it('rejects an unknown parent', function (): void {
    ['uc' => $uc] = makeCreateModuleUseCase();

    expect(fn () => $uc->execute(new CreateModuleInput(
        slug: 'subevents',
        name: 'Sub',
        redirect: null,
        icon: null,
        rootModuleId: '11111111-1111-1111-1111-111111111111',
    )))->toThrow(InvalidInput::class);
});

it('honors authorization', function (): void {
    $auth = new AllowingAuthorizer();
    $auth->denyByDefault();
    ['uc' => $uc] = makeCreateModuleUseCase(auth: $auth);

    expect(fn () => $uc->execute(new CreateModuleInput('events', 'Events', null, null, null)))
        ->toThrow(AuthorizationFailed::class);
});
