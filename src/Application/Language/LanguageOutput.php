<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Language;

use DateTimeImmutable;
use Modularize\Access\Domain\Translation\Language;

final readonly class LanguageOutput
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public bool $isDefault,
        public bool $isActive,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    public static function fromEntity(Language $language): self
    {
        return new self(
            id: $language->id->value,
            code: $language->code()->value,
            name: $language->name(),
            isDefault: $language->isDefault(),
            isActive: $language->isActive(),
            createdAt: $language->createdAt(),
            updatedAt: $language->updatedAt(),
        );
    }
}
