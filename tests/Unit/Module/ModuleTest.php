<?php

declare(strict_types=1);

use Modularize\Access\Domain\Events\ModuleCreated;
use Modularize\Access\Domain\Events\ModuleDeleted;
use Modularize\Access\Domain\Events\ModuleUpdated;
use Modularize\Access\Domain\Module\Module;
use Modularize\Access\Domain\Module\ModuleSlug;
use Modularize\Access\Domain\Shared\Uuid;
use Modularize\Access\Tests\Unit\TestDoubles\FixedClock;

function makeModule(FixedClock $clock = null, ?Uuid $rootId = null): Module
{
    return Module::create(
        id: new Uuid('11111111-1111-1111-1111-111111111111'),
        slug: new ModuleSlug('events'),
        name: 'Events',
        redirect: '/events',
        icon: 'calendar',
        rootModuleId: $rootId,
        sortOrder: 10,
        isActive: true,
        createdBy: null,
        clock: $clock ?? FixedClock::at('2026-01-01T00:00:00Z'),
    );
}

it('records a ModuleCreated event on creation', function (): void {
    $module = makeModule();
    $events = $module->pullDomainEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ModuleCreated::class);
});

it('records ModuleUpdated only when fields actually change', function (): void {
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $module = makeModule($clock);
    $module->pullDomainEvents(); // flush "created"

    // No-op update — same values
    $module->update('Events', '/events', 'calendar', null, 10, true, null, $clock);
    expect($module->pullDomainEvents())->toBeEmpty();

    // Real update
    $clock->tick('+1 day');
    $module->update('Events v2', '/events', 'calendar', null, 10, true, null, $clock);
    $events = $module->pullDomainEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ModuleUpdated::class)
        ->and($module->name())->toBe('Events v2')
        ->and($module->updatedAt()->format('Y-m-d'))->toBe('2026-01-02');
});

it('soft-deletes idempotently and records ModuleDeleted once', function (): void {
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $module = makeModule($clock);
    $module->pullDomainEvents();

    $module->softDelete(null, $clock);
    $events = $module->pullDomainEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ModuleDeleted::class)
        ->and($module->isDeleted())->toBeTrue();

    // second delete is noop
    $module->softDelete(null, $clock);
    expect($module->pullDomainEvents())->toBeEmpty();
});

it('reports root vs nested', function (): void {
    expect(makeModule()->isRoot())->toBeTrue();

    $parentId = new Uuid('22222222-2222-2222-2222-222222222222');
    expect(makeModule(rootId: $parentId)->isRoot())->toBeFalse();
});
