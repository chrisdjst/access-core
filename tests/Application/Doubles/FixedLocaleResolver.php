<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Tests\Application\Doubles;

use ModularizeRbac\Core\Application\Ports\LocaleResolver;
use ModularizeRbac\Core\Domain\Translation\LanguageCode;

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
