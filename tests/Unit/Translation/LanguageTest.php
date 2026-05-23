<?php

declare(strict_types=1);

use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Domain\Translation\Language;
use ModularizeRbac\Core\Domain\Translation\LanguageCode;
use ModularizeRbac\Core\Tests\Unit\TestDoubles\FixedClock;

function makeLanguage(bool $isDefault = false): Language
{
    return Language::create(
        id: new Uuid('55555555-5555-5555-5555-555555555555'),
        code: new LanguageCode('pt_BR'),
        name: 'Português (Brasil)',
        isDefault: $isDefault,
        isActive: true,
        clock: FixedClock::at('2026-01-01T00:00:00Z'),
    );
}

it('toggles default flag idempotently', function (): void {
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $lang = Language::create(
        id: new Uuid('55555555-5555-5555-5555-555555555555'),
        code: new LanguageCode('pt_BR'),
        name: 'Português',
        isDefault: false,
        isActive: true,
        clock: $clock,
    );

    $original = $lang->updatedAt();

    $clock->tick('+1 minute');
    $lang->markAsDefault($clock);
    expect($lang->isDefault())->toBeTrue()
        ->and($lang->updatedAt()->getTimestamp())->toBeGreaterThan($original->getTimestamp());

    $stamp = $lang->updatedAt();
    $clock->tick('+1 minute');
    $lang->markAsDefault($clock);
    expect($lang->updatedAt())->toBe($stamp); // noop

    $clock->tick('+1 minute');
    $lang->demoteFromDefault($clock);
    expect($lang->isDefault())->toBeFalse();
});

it('toggles active flag idempotently', function (): void {
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $lang = makeLanguage();
    $stamp = $lang->updatedAt();

    $clock->tick('+1 minute');
    $lang->activate($clock);
    expect($lang->updatedAt())->toBe($stamp); // already active, noop

    $clock->tick('+1 minute');
    $lang->deactivate($clock);
    expect($lang->isActive())->toBeFalse();
});
