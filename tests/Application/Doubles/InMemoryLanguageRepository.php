<?php

declare(strict_types=1);

namespace Modularize\Access\Tests\Application\Doubles;

use Modularize\Access\Application\Ports\LanguageRepository;
use Modularize\Access\Domain\Shared\Uuid;
use Modularize\Access\Domain\Translation\Language;
use Modularize\Access\Domain\Translation\LanguageCode;

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
