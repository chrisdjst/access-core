<?php

declare(strict_types=1);

use ModularizeRbac\Core\Domain\Module\ModulePermission;
use ModularizeRbac\Core\Domain\Module\ModuleSlug;
use ModularizeRbac\Core\Domain\Permission\PermissionName;
use ModularizeRbac\Core\Domain\RoleModulePermission\PermissionInheritanceResolver;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Tests\Application\Doubles\FixedClock;

/**
 * Build a ModulePermission flag set the resolver can read.
 */
function flagSet(bool $list = false, bool $view = false, bool $create = false, bool $update = false, bool $delete = false): ModulePermission
{
    static $i = 0;
    $hex = str_pad(dechex(++$i), 32, '0', STR_PAD_LEFT);
    $uuid = sprintf(
        '%s-%s-%s-%s-%s',
        substr($hex, 0, 8),
        substr($hex, 8, 4),
        substr($hex, 12, 4),
        substr($hex, 16, 4),
        substr($hex, 20, 12),
    );

    return ModulePermission::create(
        id: new Uuid($uuid),
        isListingAllowed: $list,
        isReadingAllowed: $view,
        isWritingAllowed: $create,
        isEditingAllowed: $update,
        isDeleteAllowed: $delete,
        createdBy: null,
        clock: FixedClock::at('2026-01-01T00:00:00Z'),
    );
}

it('returns true when a flag set on the requested module itself grants the action', function (): void {
    $resolver = new PermissionInheritanceResolver();

    $result = $resolver->isAllowed(
        new PermissionName('events.view'),
        flagsForSlug: fn (ModuleSlug $s) => $s->value === 'events' ? [flagSet(view: true)] : [],
        parentOfSlug: fn (ModuleSlug $s) => null,
    );

    expect($result)->toBeTrue();
});

it('returns false when no flag set anywhere in the chain grants the action', function (): void {
    $resolver = new PermissionInheritanceResolver();

    $result = $resolver->isAllowed(
        new PermissionName('events.view'),
        flagsForSlug: fn (ModuleSlug $s) => $s->value === 'events' ? [flagSet(list: true)] : [],
        parentOfSlug: fn (ModuleSlug $s) => null,
    );

    expect($result)->toBeFalse();
});

it('inherits an action granted on an ancestor module', function (): void {
    $resolver = new PermissionInheritanceResolver();

    // child: no binding; parent (events): grants view
    $parentMap = ['events.weddings' => new ModuleSlug('events')];

    $result = $resolver->isAllowed(
        new PermissionName('events.weddings.view'),
        flagsForSlug: fn (ModuleSlug $s) => $s->value === 'events' ? [flagSet(view: true)] : [],
        parentOfSlug: fn (ModuleSlug $s) => $parentMap[$s->value] ?? null,
    );

    expect($result)->toBeTrue();
});

it('inherits across two levels', function (): void {
    $resolver = new PermissionInheritanceResolver();

    $parentMap = [
        'admin.events.weddings' => new ModuleSlug('admin.events'),
        'admin.events' => new ModuleSlug('admin'),
    ];

    $result = $resolver->isAllowed(
        new PermissionName('admin.events.weddings.create'),
        flagsForSlug: fn (ModuleSlug $s) => $s->value === 'admin' ? [flagSet(create: true)] : [],
        parentOfSlug: fn (ModuleSlug $s) => $parentMap[$s->value] ?? null,
    );

    expect($result)->toBeTrue();
});

it('honors a child grant even when an ancestor would not grant the action', function (): void {
    $resolver = new PermissionInheritanceResolver();

    $parentMap = ['events.weddings' => new ModuleSlug('events')];

    $result = $resolver->isAllowed(
        new PermissionName('events.weddings.delete'),
        flagsForSlug: fn (ModuleSlug $s) => match ($s->value) {
            'events.weddings' => [flagSet(delete: true)],
            'events' => [flagSet(view: true)],
            default => [],
        },
        parentOfSlug: fn (ModuleSlug $s) => $parentMap[$s->value] ?? null,
    );

    expect($result)->toBeTrue();
});

it('considers every flag set returned for a slug — first match wins', function (): void {
    $resolver = new PermissionInheritanceResolver();

    $result = $resolver->isAllowed(
        new PermissionName('events.view'),
        flagsForSlug: fn (ModuleSlug $s) => $s->value === 'events'
            ? [flagSet(list: true), flagSet(view: true)]   // second one wins
            : [],
        parentOfSlug: fn (ModuleSlug $s) => null,
    );

    expect($result)->toBeTrue();
});

it('stops walking when it encounters a cycle and returns false without infinite-looping', function (): void {
    $resolver = new PermissionInheritanceResolver();

    // a → b → a (cycle); no slug grants view
    $parentMap = [
        'a' => new ModuleSlug('b'),
        'b' => new ModuleSlug('a'),
    ];

    $result = $resolver->isAllowed(
        new PermissionName('a.view'),
        flagsForSlug: fn (ModuleSlug $s) => [],
        parentOfSlug: fn (ModuleSlug $s) => $parentMap[$s->value] ?? null,
    );

    expect($result)->toBeFalse();
});

it('returns false when neither the module nor any ancestor has a flag set', function (): void {
    $resolver = new PermissionInheritanceResolver();

    $result = $resolver->isAllowed(
        new PermissionName('events.view'),
        flagsForSlug: fn (ModuleSlug $s) => [],
        parentOfSlug: fn (ModuleSlug $s) => null,
    );

    expect($result)->toBeFalse();
});
