<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Translation;

use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * BCP-47 / Laravel-style locale code (e.g. "en", "pt_BR", "zh-Hant").
 * Accepts either underscore or hyphen separators between the
 * language tag and the region/script subtag.
 *
 * The canonical form stored here preserves whatever the caller passes
 * after normalizing case (language lowercase, region uppercase) so the
 * code compares stably across host apps without imposing a single
 * convention on persistence.
 */
final readonly class LanguageCode
{
    private const PATTERN = '/^([A-Za-z]{2,3})([_-][A-Za-z0-9]{2,8})?$/';

    public string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw InvalidInput::of('language_code', 'Language code cannot be empty.');
        }
        if (preg_match(self::PATTERN, $trimmed, $matches) !== 1) {
            throw InvalidInput::of(
                'language_code',
                "Invalid language code: {$value}"
            );
        }

        $language = strtolower($matches[1]);
        $region = $matches[2] ?? '';
        if ($region !== '') {
            $separator = $region[0];
            $body = substr($region, 1);
            // Region/script subtag: 2-letter ISO regions in uppercase ("BR"),
            // 4-letter script tags in title case ("Hant").
            $region = strlen($body) === 4
                ? $separator.ucfirst(strtolower($body))
                : $separator.strtoupper($body);
        }

        $this->value = $language.$region;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
