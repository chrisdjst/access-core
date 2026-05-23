<?php

declare(strict_types=1);

use ModularizeRbac\Core\Domain\Audit\AuditEntry;
use ModularizeRbac\Core\Domain\Audit\AuditEventName;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Tests\Unit\TestDoubles\FixedClock;

it('records with optional actor and tenant', function (): void {
    $clock = FixedClock::at('2026-06-01T10:00:00Z');
    $entry = AuditEntry::record(
        id: new Uuid('11111111-1111-1111-1111-111111111111'),
        event: new AuditEventName('module.created'),
        actorId: new Uuid('22222222-2222-2222-2222-222222222222'),
        tenantId: new Uuid('33333333-3333-3333-3333-333333333333'),
        payload: ['module_id' => 'm1', 'slug' => 'events'],
        clock: $clock,
    );

    expect($entry->id->value)->toBe('11111111-1111-1111-1111-111111111111')
        ->and($entry->event->value)->toBe('module.created')
        ->and($entry->actorId?->value)->toBe('22222222-2222-2222-2222-222222222222')
        ->and($entry->tenantId?->value)->toBe('33333333-3333-3333-3333-333333333333')
        ->and($entry->payload)->toBe(['module_id' => 'm1', 'slug' => 'events'])
        ->and($entry->occurredAt->format('c'))->toBe('2026-06-01T10:00:00+00:00');
});

it('accepts null actor and tenant', function (): void {
    $entry = AuditEntry::record(
        id: new Uuid('11111111-1111-1111-1111-111111111111'),
        event: new AuditEventName('language.default_changed'),
        actorId: null,
        tenantId: null,
        payload: [],
        clock: FixedClock::at('2026-06-01T10:00:00Z'),
    );

    expect($entry->actorId)->toBeNull()
        ->and($entry->tenantId)->toBeNull()
        ->and($entry->payload)->toBe([]);
});
