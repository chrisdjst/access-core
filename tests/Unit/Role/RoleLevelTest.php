<?php

declare(strict_types=1);

use Modularize\Access\Domain\Role\RoleLevel;
use Modularize\Access\Exceptions\InvalidInput;

it('accepts zero and positive integers', function (int $value): void {
    expect((new RoleLevel($value))->value)->toBe($value);
})->with([0, 1, 50, 100, 9999]);

it('rejects negative levels', function (): void {
    new RoleLevel(-1);
})->throws(InvalidInput::class);

it('compares ordering', function (): void {
    $low = new RoleLevel(10);
    $high = new RoleLevel(100);

    expect($high->isHigherThan($low))->toBeTrue()
        ->and($low->isHigherThan($high))->toBeFalse()
        ->and($low->equals(new RoleLevel(10)))->toBeTrue();
});
