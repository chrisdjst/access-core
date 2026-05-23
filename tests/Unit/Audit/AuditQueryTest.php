<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Audit\AuditQuery;
use ModularizeRbac\Core\Exceptions\InvalidInput;

it('defaults to a sane window with no filters', function (): void {
    $q = new AuditQuery();

    expect($q->event)->toBeNull()
        ->and($q->actorId)->toBeNull()
        ->and($q->tenantId)->toBeNull()
        ->and($q->since)->toBeNull()
        ->and($q->until)->toBeNull()
        ->and($q->limit)->toBe(100)
        ->and($q->offset)->toBe(0);
});

it('rejects limit outside [1, 1000]', function (int $limit): void {
    new AuditQuery(limit: $limit);
})->with([0, -1, 1001, 9999])->throws(InvalidInput::class);

it('rejects negative offset', function (): void {
    new AuditQuery(offset: -1);
})->throws(InvalidInput::class);

it('rejects since > until', function (): void {
    new AuditQuery(
        since: new DateTimeImmutable('2026-06-02'),
        until: new DateTimeImmutable('2026-06-01'),
    );
})->throws(InvalidInput::class);
