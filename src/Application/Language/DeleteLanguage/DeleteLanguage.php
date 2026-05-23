<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Language\DeleteLanguage;

use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\LanguageRepository;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Exceptions\NotFound;

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
