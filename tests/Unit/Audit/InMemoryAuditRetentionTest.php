<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Audit\AuditQuery;
use ModularizeRbac\Core\Domain\Audit\AuditEntry;
use ModularizeRbac\Core\Domain\Audit\AuditEventName;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Tests\Application\Doubles\FixedClock;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryAuditRepository;

function seedRetentionEntries(InMemoryAuditRepository $repo, FixedClock $clock, int $count): void
{
    for ($i = 1; $i <= $count; $i++) {
        $repo->save(AuditEntry::record(
            id: new Uuid(sprintf('00000000-0000-0000-0000-%012d', $i)),
            event: new AuditEventName('module.created'),
            actorId: null,
            tenantId: null,
            payload: [],
            clock: $clock,
        ));
        $clock->tick('+1 day');
    }
}

it('deletes only entries strictly older than the cutoff', function (): void {
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $repo = new InMemoryAuditRepository();
    seedRetentionEntries($repo, $clock, 5); // entries at days 1..5

    $cutoff = new DateTimeImmutable('2026-01-03T00:00:00Z');
    $removed = $repo->deleteOlderThan($cutoff);

    expect($removed)->toBe(2) // days 1 and 2
        ->and($repo->count(new AuditQuery(limit: 1000)))->toBe(3);
});

it('returns zero when nothing matches the cutoff', function (): void {
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $repo = new InMemoryAuditRepository();
    seedRetentionEntries($repo, $clock, 3);

    $cutoff = new DateTimeImmutable('2025-01-01T00:00:00Z'); // in the past
    expect($repo->deleteOlderThan($cutoff))->toBe(0);
});

it('treats the cutoff itself as kept (strict <)', function (): void {
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $repo = new InMemoryAuditRepository();
    $repo->save(AuditEntry::record(
        id: new Uuid('00000000-0000-0000-0000-000000000001'),
        event: new AuditEventName('module.created'),
        actorId: null,
        tenantId: null,
        payload: [],
        clock: $clock,
    ));

    expect($repo->deleteOlderThan(new DateTimeImmutable('2026-01-01T00:00:00Z')))->toBe(0);
});

it('is empty after purging everything', function (): void {
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $repo = new InMemoryAuditRepository();
    seedRetentionEntries($repo, $clock, 3);

    $removed = $repo->deleteOlderThan(new DateTimeImmutable('2027-01-01T00:00:00Z'));

    expect($removed)->toBe(3)
        ->and($repo->count(new AuditQuery(limit: 1000)))->toBe(0);
});
