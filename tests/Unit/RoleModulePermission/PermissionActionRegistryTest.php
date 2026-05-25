<?php

declare(strict_types=1);

use ModularizeRbac\Core\Domain\Module\ModulePermission;
use ModularizeRbac\Core\Domain\Module\ModuleSlug;
use ModularizeRbac\Core\Domain\RoleModulePermission\PermissionActionRegistry;
use ModularizeRbac\Core\Domain\RoleModulePermission\PermissionFlagResolver;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Tests\Unit\TestDoubles\FixedClock;

function makeFlagSet(array $extras = []): ModulePermission
{
    return ModulePermission::create(
        id: new Uuid('11111111-1111-1111-1111-111111111111'),
        isListingAllowed: false,
        isReadingAllowed: true,
        isWritingAllowed: false,
        isEditingAllowed: false,
        isDeleteAllowed: false,
        createdBy: null,
        clock: FixedClock::at('2026-01-01T00:00:00Z'),
        extraFlags: $extras,
    );
}

it('seeds the built-in 5 actions from ModulePermission::FLAG_TO_ACTION', function (): void {
    $registry = new PermissionActionRegistry();

    expect($registry->actions())->toBe(['list', 'view', 'create', 'update', 'delete']);
});

it('accepts pre-seeded extras via the constructor', function (): void {
    $registry = new PermissionActionRegistry(['is_export_allowed' => 'export']);

    expect($registry->actions())->toBe(['list', 'view', 'create', 'update', 'delete', 'export']);
});

it('register() adds new pairs in order', function (): void {
    $registry = new PermissionActionRegistry();
    $registry->register('is_sign_allowed', 'sign');
    $registry->register('is_approve_allowed', 'approve');

    expect($registry->actions())->toBe(['list', 'view', 'create', 'update', 'delete', 'sign', 'approve']);
});

it('register() rejects malformed flag names', function (): void {
    $registry = new PermissionActionRegistry();

    expect(fn () => $registry->register('export', 'export'))->toThrow(InvalidInput::class);
    expect(fn () => $registry->register('is_NOT_allowed', 'export'))->toThrow(InvalidInput::class);
});

it('register() rejects malformed action names', function (): void {
    $registry = new PermissionActionRegistry();

    expect(fn () => $registry->register('is_export_allowed', 'EX'))->toThrow(InvalidInput::class);
    expect(fn () => $registry->register('is_export_allowed', '1export'))->toThrow(InvalidInput::class);
});

it('PermissionFlagResolver honors registered extra actions', function (): void {
    $registry = new PermissionActionRegistry(['is_export_allowed' => 'export']);
    $resolver = new PermissionFlagResolver($registry);

    $permission = makeFlagSet(['is_export_allowed' => true]);

    expect($resolver->allowedActions($permission))->toContain('view', 'export');
});

it('PermissionFlagResolver ignores extra flags that are NOT registered', function (): void {
    // Default registry has no extras.
    $resolver = new PermissionFlagResolver();

    $permission = makeFlagSet(['is_export_allowed' => true]);

    expect($resolver->allowedActions($permission))->toBe(['view']);
});

it('managedActions() includes both built-in and extras', function (): void {
    $registry = new PermissionActionRegistry(['is_sign_allowed' => 'sign']);
    $resolver = new PermissionFlagResolver($registry);

    expect($resolver->managedActions())->toBe(['list', 'view', 'create', 'update', 'delete', 'sign']);
});

it('permissionNamesFor() builds correctly-prefixed names for custom actions', function (): void {
    $registry = new PermissionActionRegistry(['is_export_allowed' => 'export']);
    $resolver = new PermissionFlagResolver($registry);

    $permission = makeFlagSet(['is_export_allowed' => true]);

    $names = array_map(fn ($n) => $n->value, $resolver->permissionNamesFor($permission, new ModuleSlug('events')));

    expect($names)->toContain('events.view', 'events.export');
});
