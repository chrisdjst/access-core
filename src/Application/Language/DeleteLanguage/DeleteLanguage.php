<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Language\DeleteLanguage;

use Modularize\Access\Application\Ports\Authorizer;
use Modularize\Access\Application\Ports\LanguageRepository;
use Modularize\Access\Application\Ports\UnitOfWork;
use Modularize\Access\Domain\Shared\Uuid;
use Modularize\Access\Exceptions\InvalidInput;
use Modularize\Access\Exceptions\NotFound;

/**
 * Use-case: delete a language. The default language is protected —
 * promoting a different language first is the host's responsibility.
 * Translations attached to deleted languages cascade via the
 * persistence adapter (the legacy contract relied on this).
 *
 * Authorization: `admin.languages.delete`.
 */
final class DeleteLanguage
{
    public function __construct(
        private readonly LanguageRepository $languages,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
    ) {
    }

    public function execute(string $rawId): void
    {
        $this->authorizer->ensure('admin.languages.delete');

        $id = new Uuid($rawId);
        $lang = $this->languages->find($id) ?? throw NotFound::of('Language', $id->value);

        if ($lang->isDefault()) {
            throw InvalidInput::of('id', 'Cannot delete the default language.');
        }

        $this->uow->transactional(function () use ($lang): void {
            $this->languages->delete($lang);
        });
    }
}
