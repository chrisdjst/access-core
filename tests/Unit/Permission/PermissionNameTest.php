<?php

declare(strict_types=1);

use Modularize\Access\Domain\Module\ModuleSlug;
use Modularize\Access\Domain\Permission\PermissionName;
use Modularize\Access\Exceptions\InvalidInput;

it('parses slug + action', function (): void {
    $name = new PermissionName('admin.events.view');

    expect($name->value)->toBe('admin.events.view')
        ->and($name->moduleSlug->value)->toBe('admin.events')
        ->and($name->action)->toBe('view');
});

it('composes from parts', function (): void {
    $name = PermissionName::fromParts(new ModuleSlug('billing'), 'create');

    expect($name->value)->toBe('billing.create')
        ->and($name->moduleSlug->value)->toBe('billing')
        ->and($name->action)->toBe('create');
});

it('parses nested slug + action', function (): void {
    $name = new PermissionName('events.sub.action');

    expect($name->moduleSlug->value)->toBe('events.sub')
        ->and($name->action)->toBe('action');
});

it('rejects malformed names', function (string $bad): void {
    new PermissionName($bad);
})->with([
    'no action' => 'events',
    'empty' => '',
    'leading dot' => '.view',
    'trailing dot' => 'events.',
    'uppercase action' => 'events.View',
    'action with dash' => 'events.read-only',
])->throws(InvalidInput::class);
