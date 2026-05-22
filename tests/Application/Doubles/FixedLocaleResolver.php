<?php

declare(strict_types=1);

namespace Modularize\Access\Tests\Application\Doubles;

use Modularize\Access\Application\Ports\LocaleResolver;
use Modularize\Access\Domain\Translation\LanguageCode;

final class FixedLocaleResolver implements LocaleResolver
{
    public function __construct(
        private LanguageCode $current,
        private ?LanguageCode $fallback = null,
    ) {
    }

    public function currentLocale(): LanguageCode
    {
        return $this->current;
    }

    public function fallbackLocale(): ?LanguageCode
    {
        return $this->fallback;
    }
}
