<?php

declare(strict_types=1);

use ModularizeRbac\Core\Domain\Module\ModuleSlug;
use ModularizeRbac\Core\Exceptions\InvalidInput;

it('accepts flat and dotted slugs', function (string $value): void {
    $slug = new ModuleSlug($value);
    expect($slug->value)->toBe($value);
})->with(['events', 'billing', 'admin.events', 'billing.invoices.draft', 'a1.b2.c3']);

it('trims surrounding whitespace', function (): void {
    $slug = new ModuleSlug('  events  ');
    expect($slug->value)->toBe('events');
});

it('compares slugs by value', function (): void {
    expect((new ModuleSlug('events'))->equals(new ModuleSlug('events')))->toBeTrue()
        ->and((new ModuleSlug('events'))->equals(new ModuleSlug('billing')))->toBeFalse();
});

it('rejects malformed slugs', function (string $bad): void {
    new ModuleSlug($bad);
})->with([
    'empty' => '',
    'leading dot' => '.events',
    'trailing dot' => 'events.',
    'double dot' => 'admin..events',
    'uppercase' => 'Events',
    'spaces' => 'admin events',
    'dash' => 'admin-events',
])->throws(InvalidInput::class);
