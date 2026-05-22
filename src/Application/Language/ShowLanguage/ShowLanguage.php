<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Language\ShowLanguage;

use Modularize\Access\Application\Language\LanguageOutput;
use Modularize\Access\Application\Ports\Authorizer;
use Modularize\Access\Application\Ports\LanguageRepository;
use Modularize\Access\Domain\Shared\Uuid;
use Modularize\Access\Exceptions\NotFound;

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
