<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Language\CreateLanguage;

use ModularizeRbac\Core\Application\Language\LanguageOutput;
use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\DomainEventDispatcher;
use ModularizeRbac\Core\Application\Ports\LanguageRepository;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Domain\Events\LanguageDefaultChanged;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\IdGenerator;
use ModularizeRbac\Core\Domain\Translation\Language;
use ModularizeRbac\Core\Exceptions\InvalidInput;

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
