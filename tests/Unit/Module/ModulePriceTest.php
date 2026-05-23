<?php

declare(strict_types=1);

use ModularizeRbac\Core\Domain\Module\ModulePrice;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Tests\Unit\TestDoubles\FixedClock;

it('normalizes currency to uppercase and validates value', function (): void {
    $price = ModulePrice::create(
        id: new Uuid('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        moduleId: new Uuid('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb'),
        value: '199.99',
        currency: 'brl',
        isActive: true,
        clock: FixedClock::at('2026-01-01T00:00:00Z'),
    );

    expect($price->value())->toBe('199.99')
        ->and($price->currency())->toBe('BRL')
        ->and($price->isActive())->toBeTrue();
});

it('rejects malformed value', function (): void {
    ModulePrice::create(
        id: new Uuid('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        moduleId: new Uuid('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb'),
        value: 'one dollar',
        currency: 'usd',
        isActive: true,
        clock: FixedClock::at('2026-01-01T00:00:00Z'),
    );
})->throws(InvalidInput::class);

it('rejects malformed currency', function (): void {
    ModulePrice::create(
        id: new Uuid('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        moduleId: new Uuid('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb'),
        value: '10.00',
        currency: 'dolar',
        isActive: true,
        clock: FixedClock::at('2026-01-01T00:00:00Z'),
    );
})->throws(InvalidInput::class);
