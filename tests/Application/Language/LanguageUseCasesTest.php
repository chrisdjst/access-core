<?php

declare(strict_types=1);

use Modularize\Access\Application\Language\CreateLanguage\CreateLanguage;
use Modularize\Access\Application\Language\CreateLanguage\CreateLanguageInput;
use Modularize\Access\Application\Language\DeleteLanguage\DeleteLanguage;
use Modularize\Access\Application\Language\SetDefaultLanguage\SetDefaultLanguage;
use Modularize\Access\Application\Language\UpdateLanguage\UpdateLanguage;
use Modularize\Access\Application\Language\UpdateLanguage\UpdateLanguageInput;
use Modularize\Access\Domain\Events\LanguageDefaultChanged;
use Modularize\Access\Exceptions\InvalidInput;
use Modularize\Access\Tests\Application\Doubles\AllowingAuthorizer;
use Modularize\Access\Tests\Application\Doubles\FixedClock;
use Modularize\Access\Tests\Application\Doubles\InMemoryLanguageRepository;
use Modularize\Access\Tests\Application\Doubles\PassthroughUnitOfWork;
use Modularize\Access\Tests\Application\Doubles\RecordingEventDispatcher;
use Modularize\Access\Tests\Application\Doubles\SequentialIdGenerator;

function langStack(): array
{
    $repo = new InMemoryLanguageRepository();
    $auth = new AllowingAuthorizer();
    $events = new RecordingEventDispatcher();
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $ids = new SequentialIdGenerator();

    return [
        'create' => new CreateLanguage($repo, $auth, new PassthroughUnitOfWork(), $events, $ids, $clock),
        'update' => new UpdateLanguage($repo, $auth, new PassthroughUnitOfWork(), $clock),
        'setDefault' => new SetDefaultLanguage($repo, $auth, new PassthroughUnitOfWork(), $events, $clock),
        'delete' => new DeleteLanguage($repo, $auth, new PassthroughUnitOfWork()),
        'repo' => $repo,
        'events' => $events,
        'clock' => $clock,
    ];
}

it('creates a language', function (): void {
    ['create' => $create] = langStack();
    $out = $create->execute(new CreateLanguageInput('pt_BR', 'Português', isDefault: false, isActive: true));

    expect($out->code)->toBe('pt_BR')->and($out->isDefault)->toBeFalse();
});

it('refuses duplicate codes', function (): void {
    ['create' => $create] = langStack();
    $create->execute(new CreateLanguageInput('pt_BR', 'Português'));
    expect(fn () => $create->execute(new CreateLanguageInput('pt_BR', 'Outro')))
        ->toThrow(InvalidInput::class);
});

it('demotes the previous default when creating a new default', function (): void {
    ['create' => $create, 'repo' => $repo, 'events' => $events] = langStack();
    $pt = $create->execute(new CreateLanguageInput('pt_BR', 'Português', isDefault: true));
    $events->dispatched = [];

    $en = $create->execute(new CreateLanguageInput('en', 'English', isDefault: true));

    $reloadedPt = $repo->find(new Modularize\Access\Domain\Shared\Uuid($pt->id));
    expect($reloadedPt)->not->toBeNull()
        ->and($reloadedPt->isDefault())->toBeFalse()
        ->and($en->isDefault)->toBeTrue()
        ->and($events->dispatched)->toHaveCount(1)
        ->and($events->dispatched[0])->toBeInstanceOf(LanguageDefaultChanged::class);
});

it('updates name and active flag', function (): void {
    ['create' => $create, 'update' => $update] = langStack();
    $pt = $create->execute(new CreateLanguageInput('pt_BR', 'Português'));
    $out = $update->execute(new UpdateLanguageInput($pt->id, 'pt_BR', 'Português (BR)', true));
    expect($out->name)->toBe('Português (BR)');
});

it('forbids editing the language code', function (): void {
    ['create' => $create, 'update' => $update] = langStack();
    $pt = $create->execute(new CreateLanguageInput('pt_BR', 'Português'));

    expect(fn () => $update->execute(new UpdateLanguageInput($pt->id, 'en', 'Português', true)))
        ->toThrow(InvalidInput::class, 'Editing a language code is not supported');
});

it('forbids deactivating the default language', function (): void {
    ['create' => $create, 'update' => $update] = langStack();
    $pt = $create->execute(new CreateLanguageInput('pt_BR', 'Português', isDefault: true));

    expect(fn () => $update->execute(new UpdateLanguageInput($pt->id, 'pt_BR', 'Português', false)))
        ->toThrow(InvalidInput::class, 'default language');
});

it('swaps default atomically and emits event', function (): void {
    ['create' => $create, 'setDefault' => $setDefault, 'events' => $events] = langStack();
    $pt = $create->execute(new CreateLanguageInput('pt_BR', 'Português', isDefault: true));
    $en = $create->execute(new CreateLanguageInput('en', 'English'));
    $events->dispatched = [];

    $out = $setDefault->execute($en->id);

    expect($out->isDefault)->toBeTrue()
        ->and($events->dispatched)->toHaveCount(1)
        ->and($events->dispatched[0])->toBeInstanceOf(LanguageDefaultChanged::class);
});

it('refuses to delete the default language', function (): void {
    ['create' => $create, 'delete' => $delete] = langStack();
    $pt = $create->execute(new CreateLanguageInput('pt_BR', 'Português', isDefault: true));

    expect(fn () => $delete->execute($pt->id))
        ->toThrow(InvalidInput::class, 'Cannot delete the default');
});

it('deletes non-default languages', function (): void {
    ['create' => $create, 'delete' => $delete, 'repo' => $repo] = langStack();
    $pt = $create->execute(new CreateLanguageInput('pt_BR', 'Português', isDefault: true));
    $en = $create->execute(new CreateLanguageInput('en', 'English'));

    $delete->execute($en->id);
    expect($repo->find(new Modularize\Access\Domain\Shared\Uuid($en->id)))->toBeNull();
});
