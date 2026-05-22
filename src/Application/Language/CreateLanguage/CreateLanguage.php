<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Language\CreateLanguage;

use Modularize\Access\Application\Language\LanguageOutput;
use Modularize\Access\Application\Ports\Authorizer;
use Modularize\Access\Application\Ports\DomainEventDispatcher;
use Modularize\Access\Application\Ports\LanguageRepository;
use Modularize\Access\Application\Ports\UnitOfWork;
use Modularize\Access\Domain\Events\LanguageDefaultChanged;
use Modularize\Access\Domain\Shared\Clock;
use Modularize\Access\Domain\Shared\IdGenerator;
use Modularize\Access\Domain\Translation\Language;
use Modularize\Access\Exceptions\InvalidInput;

/**
 * Use-case: register a new language. Code uniqueness is enforced
 * here; if the new language is marked default the previous default
 * (if any) is demoted in the same transaction so the
 * "exactly one default" invariant holds at commit time.
 *
 * Authorization: `admin.languages.create`.
 */
final class CreateLanguage
{
    public function __construct(
        private readonly LanguageRepository $languages,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
        private readonly DomainEventDispatcher $events,
        private readonly IdGenerator $ids,
        private readonly Clock $clock,
    ) {
    }

    public function execute(CreateLanguageInput $input): LanguageOutput
    {
        $this->authorizer->ensure('admin.languages.create');

        if ($this->languages->findByCode($input->code) !== null) {
            throw InvalidInput::of('code', "Language code already exists: {$input->code->value}");
        }

        $defaultChanged = null;
        $created = $this->uow->transactional(function () use ($input, &$defaultChanged): Language {
            $previousDefault = null;
            if ($input->isDefault) {
                $previousDefault = $this->languages->default();
                if ($previousDefault !== null) {
                    $previousDefault->demoteFromDefault($this->clock);
                    $this->languages->save($previousDefault);
                }
            }
            $lang = Language::create(
                id: $this->ids->nextUuid(),
                code: $input->code,
                name: $input->name,
                isDefault: $input->isDefault,
                isActive: $input->isActive,
                clock: $this->clock,
            );
            $this->languages->save($lang);

            if ($input->isDefault) {
                $defaultChanged = new LanguageDefaultChanged(
                    previousDefaultId: $previousDefault?->id,
                    newDefaultId: $lang->id,
                    occurredAt: $this->clock->now(),
                );
            }

            return $lang;
        });

        if ($defaultChanged !== null) {
            $this->events->dispatch($defaultChanged);
        }

        return LanguageOutput::fromEntity($created);
    }
}
