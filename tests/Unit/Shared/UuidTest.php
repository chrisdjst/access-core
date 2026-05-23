<?php

declare(strict_types=1);

use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;

it('accepts a canonical lowercase UUID', function (): void {
    $id = new Uuid('00000000-0000-0000-0000-000000000000');
    expect($id->value)->toBe('00000000-0000-0000-0000-000000000000');
});

it('normalizes uppercase hex to lowercase', function (): void {
    $id = new Uuid('AABBCCDD-1122-3344-5566-AABBCCDD7788');
    expect($id->value)->toBe('aabbccdd-1122-3344-5566-aabbccdd7788');
});

it('compares two UUIDs by value', function (): void {
    $a = new Uuid('aabbccdd-1122-3344-5566-aabbccdd7788');
    $b = new Uuid('AABBCCDD-1122-3344-5566-AABBCCDD7788');
    $c = new Uuid('11111111-2222-3333-4444-555555555555');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});

it('rejects malformed UUIDs', function (string $bad): void {
    new Uuid($bad);
})->with([
    'too short' => 'aabb-1122-3344-5566-aabbccdd7788',
    'no dashes' => 'aabbccdd11223344556677889900aabb',
    'non-hex' => 'gggggggg-0000-0000-0000-000000000000',
    'empty' => '',
])->throws(InvalidInput::class);
