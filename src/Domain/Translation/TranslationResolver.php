<?php

declare(strict_types=1);

namespace Modularize\Access\Domain\Translation;

/**
 * Pure resolver of "the value of field X in language Y" given a
 * pre-loaded collection of translation rows.
 *
 * Extracted from `HasTranslations::translate()`. By living as a domain
 * service rather than a method on every translatable model:
 *
 * - It can be reused by both the legacy Eloquent adapter and any
 *   future adapter without re-implementing the fallback rules.
 * - It composes with a `LanguageRepository` (held by the application
 *   layer) to resolve the default fallback language id when none was
 *   supplied by the caller.
 *
 * Resolution order:
 *   1. The translation for (field, requested locale) if it exists.
 *   2. The translation for (field, fallback locale) if a fallback
 *      was supplied and differs from the requested one.
 *   3. The raw default value provided by the caller (e.g. the
 *      `name` column on the owning aggregate).
 */
final class TranslationResolver
{
    /**
     * @param  iterable<Translation>  $translations  the owner's pre-loaded translations
     * @param  array<string, LanguageCode>  $languageCodesById  index used to map Translation::languageId -> LanguageCode
     */
    public function resolve(
        iterable $translations,
        array $languageCodesById,
        string $field,
        LanguageCode $requested,
        ?LanguageCode $fallback,
        ?string $defaultValue,
    ): ?string {
        $requestedMatch = null;
        $fallbackMatch = null;

        foreach ($translations as $translation) {
            if ($translation->field !== $field) {
                continue;
            }
            $code = $languageCodesById[$translation->languageId->value] ?? null;
            if ($code === null) {
                continue;
            }
            if ($code->equals($requested) && $requestedMatch === null) {
                $requestedMatch = $translation;
            } elseif ($fallback !== null && $code->equals($fallback) && $fallbackMatch === null) {
                $fallbackMatch = $translation;
            }
        }

        if ($requestedMatch !== null) {
            return $requestedMatch->value();
        }
        if ($fallbackMatch !== null && $fallback !== null && ! $fallback->equals($requested)) {
            return $fallbackMatch->value();
        }

        return $defaultValue;
    }
}
