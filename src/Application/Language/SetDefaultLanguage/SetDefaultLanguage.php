<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Language\SetDefaultLanguage;

use Modularize\Access\Application\Language\LanguageOutput;
use Modularize\Access\Application\Ports\Authorizer;
use Modularize\Access\Application\Ports\DomainEventDispatcher;
use Modularize\Access\Application\Ports\LanguageRepository;
use Modularize\Access\Application\Ports\UnitOfWork;
use Modularize\Access\Domain\Events\LanguageDefaultChanged;
use Modularize\Access\Domain\Shared\Clock;
use Modularize\Access\Domain\Shared\Uuid;
use Modularize\Access\Exceptions\InvalidInput;
use Modularize\Access\Exceptions\NotFound;

/**
 * Use-case: mark a language as the system default. Atomically demotes
 * the previous default and emits a `LanguageDefaultChanged` event so
 * subscribers (e.g. translation caches) can invalidate.
 *
 * Authorization: `admin.languages.update`.
 */
final class SetDefaultLanguage
{
    public function __construct(
        private readonly LanguageRepository $languages,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
        private readonly DomainEventDispatcher $events,
        private readonly Clock $clock,
    ) {
    }

    public function execute(string $rawId): LanguageOutput
    {
        $this->authorizer->ensure('admin.languages.update');

        $id = new Uuid($rawId);
        $next = $this->languages->find($id) ?? throw NotFound::of('Language', $id->value);

        if (! $next->isActive()) {
            throw InvalidInput::of('id', 'Cannot mark an inactive language as default.');
        }

        if ($next->isDefault()) {
            return LanguageOutput::fromEntity($next);
        }

        $previous = $this->languages->default();

        $this->uow->transactional(function () use ($next, $previous): void {
            if ($previous !== null) {
                $previous->demoteFromDefault($this->clock);
                $this->languages->save($previous);
            }
            $next->markAsDefault($this->clock);
            $this->languages->save($next);
        });

        $this->events->dispatch(new LanguageDefaultChanged(
            previousDefaultId: $previous?->id,
            newDefaultId: $next->id,
            occurredAt: $this->clock->now(),
        ));

        return LanguageOutput::fromEntity($next);
    }
}
