<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Language\UpdateLanguage;

use Modularize\Access\Application\Language\LanguageOutput;
use Modularize\Access\Application\Ports\Authorizer;
use Modularize\Access\Application\Ports\LanguageRepository;
use Modularize\Access\Application\Ports\UnitOfWork;
use Modularize\Access\Domain\Shared\Clock;
use Modularize\Access\Domain\Translation\Language;
use Modularize\Access\Exceptions\InvalidInput;
use Modularize\Access\Exceptions\NotFound;

/**
 * Use-case: update mutable fields of a language. Default-flag changes
 * are handled by {@see SetDefaultLanguage}.
 *
 * Authorization: `admin.languages.update`.
 */
final class UpdateLanguage
{
    public function __construct(
        private readonly LanguageRepository $languages,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
        private readonly Clock $clock,
    ) {
    }

    public function execute(UpdateLanguageInput $input): LanguageOutput
    {
        $this->authorizer->ensure('admin.languages.update');

        $lang = $this->languages->find($input->id) ?? throw NotFound::of('Language', $input->id->value);

        if (! $lang->code()->equals($input->code)) {
            $existing = $this->languages->findByCode($input->code);
            if ($existing !== null && ! $existing->id->equals($lang->id)) {
                throw InvalidInput::of('code', "Language code already exists: {$input->code->value}");
            }
        }

        if ($lang->isDefault() && ! $input->isActive) {
            throw InvalidInput::of('is_active', 'Cannot deactivate the default language.');
        }

        $this->uow->transactional(function () use ($lang, $input): void {
            $this->rename($lang, $input);
            $this->languages->save($lang);
        });

        return LanguageOutput::fromEntity($lang);
    }

    private function rename(Language $lang, UpdateLanguageInput $input): void
    {
        // Code mutation isn't exposed by the entity surface today —
        // we use a destructive replace via reconstitute in a future
        // increment if needed. For now we drive the mutable surface:
        $lang->rename($input->name, $this->clock);
        if ($input->isActive) {
            $lang->activate($this->clock);
        } else {
            $lang->deactivate($this->clock);
        }
        // Code edit: not yet supported by domain. If/when needed we
        // add a setter behind an invariant ("code must remain valid
        // BCP-47, no duplicates"). The legacy contract allowed code
        // edits — call this out in CHANGELOG as a v1.0 BREAKING.
        if (! $lang->code()->equals($input->code)) {
            throw InvalidInput::of('code', 'Editing a language code is not supported in v1.0. Create a new language and migrate data.');
        }
    }
}
