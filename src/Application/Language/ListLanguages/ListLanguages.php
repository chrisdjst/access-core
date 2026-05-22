<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Language\ListLanguages;

use Modularize\Access\Application\Language\LanguageOutput;
use Modularize\Access\Application\Ports\Authorizer;
use Modularize\Access\Application\Ports\LanguageRepository;

/**
 * Use-case: list all languages.
 *
 * Authorization: `admin.languages.view`.
 */
final class ListLanguages
{
    public function __construct(
        private readonly LanguageRepository $languages,
        private readonly Authorizer $authorizer,
    ) {
    }

    /**
     * @return list<LanguageOutput>
     */
    public function execute(): array
    {
        $this->authorizer->ensure('admin.languages.view');

        $output = [];
        foreach ($this->languages->all() as $lang) {
            $output[] = LanguageOutput::fromEntity($lang);
        }

        return $output;
    }
}
