<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Language\CreateLanguage;

use ModularizeRbac\Core\Domain\Translation\LanguageCode;
use ModularizeRbac\Core\Exceptions\InvalidInput;

final readonly class CreateLanguageInput
{
    public LanguageCode $code;
    public string $name;
    public bool $isDefault;
    public bool $isActive;

    public function __construct(string $code, string $name, bool $isDefault = false, bool $isActive = true)
    {
        if (trim($name) === '') {
            throw InvalidInput::of('name', 'Language name cannot be empty.');
        }
        $this->code = new LanguageCode($code);
        $this->name = $name;
        $this->isDefault = $isDefault;
        $this->isActive = $isActive;
    }
}
