<?php

declare(strict_types=1);

use ModularizeRbac\Core\Domain\Translation\LanguageCode;
use ModularizeRbac\Core\Exceptions\InvalidInput;

it('normalizes case: language lowercase, region uppercase', function (string $input, string $expected): void {
    $code = new LanguageCode($input);
    expect($code->value)->toBe($expected);
})->with([
    ['en', 'en'],
    ['EN', 'en'],
    ['pt_br', 'pt_BR'],
    ['pt-br', 'pt-BR'],
    ['ZH_HANT', 'zh_Hant'],
]);

it('rejects malformed codes', function (string $bad): void {
    new LanguageCode($bad);
})->with([
    'empty' => '',
    'single letter' => 'e',
    'too many digits' => '123',
    'unexpected separator' => 'en/us',
    'too long lang' => 'engl',
])->throws(InvalidInput::class);

it('compares codes by normalized value', function (): void {
    expect((new LanguageCode('PT_br'))->equals(new LanguageCode('pt_BR')))->toBeTrue()
        ->and((new LanguageCode('pt_BR'))->equals(new LanguageCode('en')))->toBeFalse();
});
