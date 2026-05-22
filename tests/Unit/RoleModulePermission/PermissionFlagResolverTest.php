<?php

declare(strict_types=1);

use Modularize\Access\Domain\Module\ModulePermission;
use Modularize\Access\Domain\Module\ModuleSlug;
use Modularize\Access\Domain\RoleModulePermission\PermissionFlagResolver;
use Modularize\Access\Domain\Shared\Uuid;
use Modularize\Access\Tests\Unit\TestDoubles\FixedClock;

function flagsAs(bool $list, bool $view, bool $create, bool $update, bool $delete): ModulePermission
{
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

it('returns no actions for an empty flag set', function (): void {
    $resolver = new PermissionFlagResolver();
    expect($resolver->allowedActions(flagsAs(false, false, false, false, false)))->toBe([]);
});

it('returns all actions when all flags are on', function (): void {
    $resolver = new PermissionFlagResolver();
    expect($resolver->allowedActions(flagsAs(true, true, true, true, true)))
        ->toBe(['list', 'view', 'create', 'update', 'delete']);
});

it('returns only the flipped flags', function (): void {
    $resolver = new PermissionFlagResolver();
    expect($resolver->allowedActions(flagsAs(true, false, true, false, false)))
        ->toBe(['list', 'create']);
});

it('lists the managed canonical action set', function (): void {
    expect((new PermissionFlagResolver())->managedActions())
        ->toBe(['list', 'view', 'create', 'update', 'delete']);
});

it('builds permission names scoped to a slug', function (): void {
    $resolver = new PermissionFlagResolver();
    $names = $resolver->permissionNamesFor(
        flagsAs(true, true, false, false, false),
        new ModuleSlug('admin.events'),
    );

    expect(array_map(fn ($n) => $n->value, $names))
        ->toBe(['admin.events.list', 'admin.events.view']);
});
