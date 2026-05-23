<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Language\UpdateLanguage;

use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Domain\Translation\LanguageCode;
use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Input for {@see UpdateLanguage}. Editing `code` is intentionally
 * supported (the legacy contract did) but uniqueness is re-checked
 * in the use-case. The `isDefault` flag is NOT touched here — there
 * is a dedicated {@see SetDefaultLanguage} use-case for that swap.
 */
final readonly class UpdateLanguageInput
{
    public Uuid $id;
    public LanguageCode $code;
    public string $name;
    public bool $isActive;

    public function __construct(string $id, string $code, string $name, bool $isActive)
    {
        if (trim($name) === '') {
            throw InvalidInput::of('name', 'Language name cannot be empty.');
        }
        $this->id = new Uuid($id);
        $this->code = new LanguageCode($code);
        $this->name = $name;
        $this->isActive = $isActive;
    }
}
