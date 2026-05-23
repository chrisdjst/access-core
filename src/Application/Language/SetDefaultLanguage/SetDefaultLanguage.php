<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Language\SetDefaultLanguage;

use ModularizeRbac\Core\Application\Language\LanguageOutput;
use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\DomainEventDispatcher;
use ModularizeRbac\Core\Application\Ports\LanguageRepository;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Domain\Events\LanguageDefaultChanged;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Exceptions\NotFound;

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
