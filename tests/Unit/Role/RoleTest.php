<?php

declare(strict_types=1);

use Modularize\Access\Domain\Role\GuardName;
use Modularize\Access\Domain\Role\Role;
use Modularize\Access\Domain\Role\RoleLevel;
use Modularize\Access\Domain\Shared\Uuid;
use Modularize\Access\Tests\Unit\TestDoubles\FixedClock;

function makeRole(?Uuid $tenantId = null, bool $isSystem = false): Role
{
    return Role::create(
        id: new Uuid('33333333-3333-3333-3333-333333333333'),
        name: 'editor',
        displayName: 'Editor',
        guard: new GuardName('admin'),
        tenantId: $tenantId,
        level: new RoleLevel(50),
        isSystem: $isSystem,
        clock: FixedClock::at('2026-01-01T00:00:00Z'),
    );
}

it('reports global when no tenant is set', function (): void {
    expect(makeRole()->isGlobal())->toBeTrue();
});

it('reports tenant ownership when set', function (): void {
    $tenant = new Uuid('44444444-4444-4444-4444-444444444444');
    expect(makeRole($tenant)->isGlobal())->toBeFalse();
});

it('updates display name idempotently', function (): void {
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $role = Role::create(
        id: new Uuid('33333333-3333-3333-3333-333333333333'),
        name: 'editor',
        displayName: 'Editor',
        guard: new GuardName('admin'),
        tenantId: null,
        level: new RoleLevel(50),
        isSystem: false,
        clock: $clock,
    );

    $original = $role->updatedAt();

    $clock->tick('+1 minute');
    $role->changeDisplayName('Editor', $clock);
    expect($role->updatedAt())->toBe($original);

    $clock->tick('+1 minute');
    $role->changeDisplayName('Senior Editor', $clock);
    expect($role->displayName())->toBe('Senior Editor')
        ->and($role->updatedAt()->getTimestamp())->toBeGreaterThan($original->getTimestamp());
});
