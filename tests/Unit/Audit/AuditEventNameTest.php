<?php

declare(strict_types=1);

use ModularizeRbac\Core\Domain\Audit\AuditEventName;
use ModularizeRbac\Core\Exceptions\InvalidInput;

it('accepts dotted snake_case names', function (string $value): void {
    expect((new AuditEventName($value))->value)->toBe($value);
})->with([
    'module.created',
    'role.permissions_changed',
    'language.default_changed',
    'audit.replay_started',
    'a.b.c',
]);

it('rejects malformed names', function (string $bad): void {
    new AuditEventName($bad);
})->with([
    'empty' => '',
    'no dot' => 'created',
    'leading dot' => '.created',
    'trailing dot' => 'module.',
    'double dot' => 'module..created',
    'uppercase' => 'Module.Created',
    'dash segment' => 'module.was-created',
    'leading digit' => '1.created',
])->throws(InvalidInput::class);

it('compares by value', function (): void {
    expect((new AuditEventName('module.created'))->equals(new AuditEventName('module.created')))->toBeTrue()
        ->and((new AuditEventName('module.created'))->equals(new AuditEventName('module.updated')))->toBeFalse();
});
