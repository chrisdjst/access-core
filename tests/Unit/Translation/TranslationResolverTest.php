<?php

declare(strict_types=1);

use Modularize\Access\Domain\Shared\Uuid;
use Modularize\Access\Domain\Translation\LanguageCode;
use Modularize\Access\Domain\Translation\Translation;
use Modularize\Access\Domain\Translation\TranslationResolver;
use Modularize\Access\Tests\Unit\TestDoubles\FixedClock;

function makeTranslation(string $languageId, string $field, string $value): Translation
{
    return Translation::create(
        id: new Uuid('11111111-1111-1111-1111-111111111111'),
        translatableType: 'module',
        translatableId: new Uuid('22222222-2222-2222-2222-222222222222'),
        languageId: new Uuid($languageId),
        field: $field,
        value: $value,
        clock: FixedClock::at('2026-01-01T00:00:00Z'),
    );
}

it('returns the translation for the requested locale when present', function (): void {
    $resolver = new TranslationResolver();
    $ptId = '00000000-0000-0000-0000-000000000001';
    $enId = '00000000-0000-0000-0000-000000000002';
    $result = $resolver->resolve(
        translations: [
            makeTranslation($ptId, 'name', 'Eventos'),
            makeTranslation($enId, 'name', 'Events'),
        ],
        languageCodesById: [
            $ptId => new LanguageCode('pt_BR'),
            $enId => new LanguageCode('en'),
        ],
        field: 'name',
        requested: new LanguageCode('en'),
        fallback: new LanguageCode('pt_BR'),
        defaultValue: 'raw',
    );

    expect($result)->toBe('Events');
});

it('falls back to the fallback locale when requested is missing', function (): void {
    $resolver = new TranslationResolver();
    $ptId = '00000000-0000-0000-0000-000000000001';
    $result = $resolver->resolve(
        translations: [makeTranslation($ptId, 'name', 'Eventos')],
        languageCodesById: [$ptId => new LanguageCode('pt_BR')],
        field: 'name',
        requested: new LanguageCode('en'),
        fallback: new LanguageCode('pt_BR'),
        defaultValue: 'raw',
    );

    expect($result)->toBe('Eventos');
});

it('falls back to the default value when neither locale has a translation', function (): void {
    $resolver = new TranslationResolver();
    $result = $resolver->resolve(
        translations: [],
        languageCodesById: [],
        field: 'name',
        requested: new LanguageCode('en'),
        fallback: new LanguageCode('pt_BR'),
        defaultValue: 'raw value',
    );

    expect($result)->toBe('raw value');
});

it('ignores translations for other fields', function (): void {
    $resolver = new TranslationResolver();
    $ptId = '00000000-0000-0000-0000-000000000001';
    $result = $resolver->resolve(
        translations: [
            makeTranslation($ptId, 'description', 'Descrição'),
        ],
        languageCodesById: [$ptId => new LanguageCode('pt_BR')],
        field: 'name',
        requested: new LanguageCode('pt_BR'),
        fallback: null,
        defaultValue: 'raw',
    );

    expect($result)->toBe('raw');
});

it('returns null when there is no translation and no default value', function (): void {
    $resolver = new TranslationResolver();
    expect($resolver->resolve([], [], 'name', new LanguageCode('en'), null, null))->toBeNull();
});

it('does not double-consult the fallback when it equals the requested locale', function (): void {
    $resolver = new TranslationResolver();
    $result = $resolver->resolve(
        translations: [],
        languageCodesById: [],
        field: 'name',
        requested: new LanguageCode('en'),
        fallback: new LanguageCode('en'),
        defaultValue: 'raw',
    );
    expect($result)->toBe('raw');
});
