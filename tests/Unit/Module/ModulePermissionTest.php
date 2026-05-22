<?php

declare(strict_types=1);

use Modularize\Access\Domain\Module\ModulePermission;
use Modularize\Access\Domain\Shared\Uuid;
use Modularize\Access\Tests\Unit\TestDoubles\FixedClock;

function newFlags(bool $list, bool $view, bool $create, bool $update, bool $delete): ModulePermission
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

it('exposes flags in canonical order', function (): void {
    $perm = newFlags(true, false, true, false, false);

    expect(array_keys($perm->flags()))->toBe([
        'isListingAllowed',
        'isReadingAllowed',
        'isWritingAllowed',
        'isEditingAllowed',
        'isDeleteAllowed',
    ])->and($perm->flags())->toBe([
        'isListingAllowed' => true,
        'isReadingAllowed' => false,
        'isWritingAllowed' => true,
        'isEditingAllowed' => false,
        'isDeleteAllowed' => false,
    ]);
});

it('maps every flag key to an action in FLAG_TO_ACTION', function (): void {
    $perm = newFlags(false, false, false, false, false);

    foreach (array_keys($perm->flags()) as $flag) {
        expect(ModulePermission::FLAG_TO_ACTION)->toHaveKey($flag);
    }

    expect(array_values(ModulePermission::FLAG_TO_ACTION))
        ->toBe(['list', 'view', 'create', 'update', 'delete']);
});

it('deactivates idempotently', function (): void {
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $perm = newFlags(true, true, true, true, true);
    $original = $perm->updatedAt();

    $clock->tick('+1 minute');
    $perm->deactivate(null, $clock);
    expect($perm->isActive())->toBeFalse()
        ->and($perm->updatedAt()->getTimestamp())->toBeGreaterThan($original->getTimestamp());

    $stamp = $perm->updatedAt();
    $clock->tick('+1 minute');
    $perm->deactivate(null, $clock);
    expect($perm->updatedAt())->toBe($stamp);
});
