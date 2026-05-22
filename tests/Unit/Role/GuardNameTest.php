<?php

declare(strict_types=1);

use Modularize\Access\Domain\Role\GuardName;
use Modularize\Access\Exceptions\InvalidInput;

it('accepts common guard names', function (string $value): void {
    $guard = new GuardName($value);
    expect($guard->value)->toBe($value);
})->with(['web', 'admin', 'api', 'partner_portal', 'api-v2']);

it('rejects malformed guard names', function (string $bad): void {
    new GuardName($bad);
})->with([
    'empty' => '',
    'uppercase' => 'Admin',
    'leading digit' => '1admin',
    'spaces' => 'admin guard',
])->throws(InvalidInput::class);
