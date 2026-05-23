<?php

declare(strict_types=1);

use ModularizeRbac\Core\Domain\Module\ModulePermission;
use ModularizeRbac\Core\Domain\Module\ModuleSlug;
use ModularizeRbac\Core\Domain\Permission\PermissionName;
use ModularizeRbac\Core\Domain\RoleModulePermission\RoleModulePermissionSynchronizer;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Tests\Unit\TestDoubles\FixedClock;

function desiredFlags(
    bool $list = false,
    bool $view = false,
    bool $create = false,
    bool $update = false,
    bool $delete = false,
): ModulePermission {
    return ModulePermission::create(
        id: new Uuid('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        isListingAllowed: $list,
        isReadingAllowed: $view,
        isWritingAllowed: $create,
        isEditingAllowed: $update,
        isDeleteAllowed: $delete,
        createdBy: null,
        clock: FixedClock::at('2026-01-01T00:00:00Z'),
    );
}

/**
 * @param  list<string>  $names
 * @return list<PermissionName>
 */
function permNames(array $names): array
{
    return array_map(fn (string $n) => new PermissionName($n), $names);
}

it('grants every action when the role had none', function (): void {
    $sync = new RoleModulePermissionSynchronizer();
    $plan = $sync->diff(
        new ModuleSlug('events'),
        desiredFlags(view: true, create: true),
        permNames([]),
    );

    expect(array_map(fn ($p) => $p->value, $plan->toGrant))
        ->toBe(['events.view', 'events.create'])
        ->and($plan->toRevoke)->toBe([])
        ->and($plan->isNoop())->toBeFalse();
});

it('revokes managed actions that are no longer desired', function (): void {
    $sync = new RoleModulePermissionSynchronizer();
    $plan = $sync->diff(
        new ModuleSlug('events'),
        desiredFlags(view: true),
        permNames(['events.view', 'events.create', 'events.update']),
    );

    expect(array_map(fn ($p) => $p->value, $plan->toRevoke))
        ->toBe(['events.create', 'events.update'])
        ->and($plan->toGrant)->toBe([]);
});

it('does NOT touch non-managed actions like manage/sign/approve', function (): void {
    $sync = new RoleModulePermissionSynchronizer();
    $plan = $sync->diff(
        new ModuleSlug('events'),
        desiredFlags(view: true),
        permNames(['events.view', 'events.manage', 'events.approve']),
    );

    expect($plan->toGrant)->toBe([])
        ->and($plan->toRevoke)->toBe([]);
});

it('does NOT touch permissions for other modules even if they share an action name', function (): void {
    $sync = new RoleModulePermissionSynchronizer();
    $plan = $sync->diff(
        new ModuleSlug('events'),
        desiredFlags(view: true),
        permNames(['events.view', 'billing.view', 'billing.create']),
    );

    expect($plan->toGrant)->toBe([])
        ->and($plan->toRevoke)->toBe([]);
});

it('returns a noop plan when the diff is empty', function (): void {
    $sync = new RoleModulePermissionSynchronizer();
    $plan = $sync->diff(
        new ModuleSlug('events'),
        desiredFlags(view: true, create: true),
        permNames(['events.view', 'events.create']),
    );

    expect($plan->isNoop())->toBeTrue();
});

it('on unbind, revokes every managed and non-managed permission for the slug', function (): void {
    $sync = new RoleModulePermissionSynchronizer();
    $toRevoke = $sync->permissionsToRevokeOnUnbind(
        new ModuleSlug('events'),
        permNames(['events.view', 'events.manage', 'billing.view']),
    );

    expect(array_map(fn ($p) => $p->value, $toRevoke))
        ->toBe(['events.view', 'events.manage']);
});
