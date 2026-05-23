<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Domain\Translation\Translation;

interface TranslationRepository
{
    /**
     * Return all translations attached to a translatable subject
     * (identified by polymorphic type + id). Used by readers/resources
     * to feed {@see \ModularizeRbac\Core\Domain\Translation\TranslationResolver}.
     *
     * @return list<Translation>
     */
    public function forSubject(string $translatableType, Uuid $translatableId): array;

    /**
     * Upsert a translation row for (subject, language, field).
     */
    public function save(Translation $translation): void;

    /**
     * Delete the translation row identified by (subject, language, field).
     * No-op when nothing matches.
     */
    public function deleteForSubjectField(
        string $translatableType,
        Uuid $translatableId,
        Uuid $languageId,
        string $field,
    ): void;
}
