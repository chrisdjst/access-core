<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Tests\Application\Doubles;

use ModularizeRbac\Core\Application\Ports\LanguageRepository;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Domain\Translation\Language;
use ModularizeRbac\Core\Domain\Translation\LanguageCode;

final class InMemoryLanguageRepository implements LanguageRepository
{
    /** @var array<string, Language> */
    private array $byId = [];

    public function find(Uuid $id): ?Language
    {
        return $this->byId[$id->value] ?? null;
    }

    public function findByCode(LanguageCode $code): ?Language
    {
        foreach ($this->byId as $lang) {
            if ($lang->code()->equals($code)) {
                return $lang;
            }
        }

        return null;
    }

    public function default(): ?Language
    {
        foreach ($this->byId as $lang) {
            if ($lang->isDefault()) {
                return $lang;
            }
        }

        return null;
    }

    public function all(): array
    {
        return array_values($this->byId);
    }

    public function save(Language $language): void
    {
        $this->byId[$language->id->value] = $language;
    }

    public function delete(Language $language): void
    {
        unset($this->byId[$language->id->value]);
    }
}
