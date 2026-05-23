<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use ModularizeRbac\Core\Domain\Translation\LanguageCode;

/**
 * Port for asking the host environment what locale the current
 * request/CLI invocation is operating under, plus the fallback to
 * use when a translation is missing.
 *
 * The Laravel adapter delegates to `App::getLocale()` and
 * `config('app.fallback_locale')`; CLI tools can implement this from
 * a config flag; tests pin both via the in-memory double.
 */
interface LocaleResolver
{
    public function currentLocale(): LanguageCode;

    public function fallbackLocale(): ?LanguageCode;
}
