<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Language\ShowLanguage;

use ModularizeRbac\Core\Application\Language\LanguageOutput;
use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\LanguageRepository;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\NotFound;

/**
 * Use-case: fetch a single language.
 *
 * Authorization: `admin.languages.view`.
 */
final class ShowLanguage
{
    public function __construct(
        private readonly LanguageRepository $languages,
        private readonly Authorizer $authorizer,
    ) {
    }

    public function execute(string $rawId): LanguageOutput
    {
        $this->authorizer->ensure('admin.languages.view');

        $id = new Uuid($rawId);
        $lang = $this->languages->find($id) ?? throw NotFound::of('Language', $id->value);

        return LanguageOutput::fromEntity($lang);
    }
}
