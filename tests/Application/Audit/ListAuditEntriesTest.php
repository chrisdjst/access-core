<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Audit\ListAuditEntries\ListAuditEntries;
use ModularizeRbac\Core\Application\Audit\ListAuditEntries\ListAuditEntriesInput;
use ModularizeRbac\Core\Domain\Audit\AuditEntry;
use ModularizeRbac\Core\Domain\Audit\AuditEventName;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\AuthorizationFailed;
use ModularizeRbac\Core\Tests\Application\Doubles\AllowingAuthorizer;
use ModularizeRbac\Core\Tests\Application\Doubles\FixedClock;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryAuditRepository;

function seedAudit(InMemoryAuditRepository $repo, FixedClock $clock): array
{
    $a = AuditEntry::record(
        id: new Uuid('00000000-0000-0000-0000-000000000001'),
        event: new AuditEventName('module.created'),
        actorId: new Uuid('11111111-1111-1111-1111-111111111111'),
        tenantId: new Uuid('22222222-2222-2222-2222-222222222222'),
        payload: ['module_id' => 'm1'],
        clock: $clock,
    );
    $clock->tick('+1 hour');
    $b = AuditEntry::record(
        id: new Uuid('00000000-0000-0000-0000-000000000002'),
        event: new AuditEventName('role.permissions_changed'),
        actorId: new Uuid('11111111-1111-1111-1111-111111111111'),
        tenantId: null,
        payload: ['role_id' => 'r1'],
        clock: $clock,
    );
    $clock->tick('+1 hour');
    $c = AuditEntry::record(
        id: new Uuid('00000000-0000-0000-0000-000000000003'),
        event: new AuditEventName('language.default_changed'),
        actorId: null,
        tenantId: new Uuid('22222222-2222-2222-2222-222222222222'),
        payload: ['new_default' => 'pt_BR'],
        clock: $clock,
    );
    $repo->save($a);
    $repo->save($b);
    $repo->save($c);

    return [$a, $b, $c];
}

function useCase(InMemoryAuditRepository $repo, AllowingAuthorizer $auth = null): ListAuditEntries
{
    return new ListAuditEntries(entries: $repo, authorizer: $auth ?? new AllowingAuthorizer());
}

it('returns most-recent-first paginated results', function (): void {
    $repo = new InMemoryAuditRepository();
    seedAudit($repo, FixedClock::at('2026-06-01T10:00:00Z'));

    $output = useCase($repo)->execute(new ListAuditEntriesInput());

    expect($output->total)->toBe(3)
        ->and(array_map(fn ($e) => $e->event, $output->entries))
        ->toBe(['language.default_changed', 'role.permissions_changed', 'module.created']);
});

it('filters by event name', function (): void {
    $repo = new InMemoryAuditRepository();
    seedAudit($repo, FixedClock::at('2026-06-01T10:00:00Z'));

    $output = useCase($repo)->execute(new ListAuditEntriesInput(event: 'role.permissions_changed'));

    expect($output->total)->toBe(1)
        ->and($output->entries[0]->event)->toBe('role.permissions_changed');
});

it('filters by actor', function (): void {
    $repo = new InMemoryAuditRepository();
    seedAudit($repo, FixedClock::at('2026-06-01T10:00:00Z'));

    $output = useCase($repo)->execute(new ListAuditEntriesInput(
        actorId: '11111111-1111-1111-1111-111111111111',
    ));

    expect($output->total)->toBe(2);
});

it('filters by tenant — skips entries with null tenant', function (): void {
    $repo = new InMemoryAuditRepository();
    seedAudit($repo, FixedClock::at('2026-06-01T10:00:00Z'));

    $output = useCase($repo)->execute(new ListAuditEntriesInput(
        tenantId: '22222222-2222-2222-2222-222222222222',
    ));

    expect($output->total)->toBe(2)
        ->and(array_map(fn ($e) => $e->event, $output->entries))
        ->toBe(['language.default_changed', 'module.created']);
});

it('respects limit/offset', function (): void {
    $repo = new InMemoryAuditRepository();
    seedAudit($repo, FixedClock::at('2026-06-01T10:00:00Z'));

    $output = useCase($repo)->execute(new ListAuditEntriesInput(limit: 2, offset: 1));

    expect($output->total)->toBe(3)
        ->and($output->entries)->toHaveCount(2)
        ->and($output->entries[0]->event)->toBe('role.permissions_changed');
});

it('filters by time window', function (): void {
    $repo = new InMemoryAuditRepository();
    seedAudit($repo, FixedClock::at('2026-06-01T10:00:00Z'));

    $output = useCase($repo)->execute(new ListAuditEntriesInput(
        since: '2026-06-01T10:30:00Z',
        until: '2026-06-01T11:30:00Z',
    ));

    expect($output->total)->toBe(1)
        ->and($output->entries[0]->event)->toBe('role.permissions_changed');
});

it('enforces admin.audit.view authorization', function (): void {
    $repo = new InMemoryAuditRepository();
    $auth = new AllowingAuthorizer();
    $auth->denyByDefault();

    useCase($repo, $auth)->execute(new ListAuditEntriesInput());
})->throws(AuthorizationFailed::class);
