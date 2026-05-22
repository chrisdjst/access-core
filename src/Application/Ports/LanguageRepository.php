<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Ports;

use Modularize\Access\Domain\Shared\Uuid;
use Modularize\Access\Domain\Translation\Language;
use Modularize\Access\Domain\Translation\LanguageCode;

interface LanguageRepository
{
    public function find(Uuid $id): ?Language;

    public function findByCode(LanguageCode $code): ?Language;

    public function default(): ?Language;

    /**
     * @return list<Language>
     */
    public function all(): array;

    public function save(Language $language): void;

    public function delete(Language $language): void;
}
