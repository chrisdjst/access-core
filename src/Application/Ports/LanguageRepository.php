<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Domain\Translation\Language;
use ModularizeRbac\Core\Domain\Translation\LanguageCode;

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
