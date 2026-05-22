<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Module\CreateModule;

use Modularize\Access\Domain\Module\ModuleSlug;
use Modularize\Access\Domain\Shared\Uuid;
use Modularize\Access\Exceptions\InvalidInput;

/**
 * Input contract for {@see CreateModule}. The HTTP adapter constructs
 * one from a validated FormRequest; CLI callers build one directly.
 *
 * Construction validates business-level invariants — the value
 * objects throw {@see InvalidInput} on malformed slugs/uuids/names.
 */
final readonly class CreateModuleInput
{
    public ModuleSlug $slug;
    public string $name;
    public ?string $redirect;
    public ?string $icon;
    public ?Uuid $rootModuleId;
    public int $sortOrder;
    public bool $isActive;

    public function __construct(
        string $slug,
        string $name,
        ?string $redirect,
        ?string $icon,
        ?string $rootModuleId,
        int $sortOrder = 0,
        bool $isActive = true,
    ) {
        if (trim($name) === '') {
            throw InvalidInput::of('name', 'Module name cannot be empty.');
        }
        if ($sortOrder < 0) {
            throw InvalidInput::of('sort_order', 'Sort order must be >= 0.');
        }

        $this->slug = new ModuleSlug($slug);
        $this->name = $name;
        $this->redirect = $redirect;
        $this->icon = $icon;
        $this->rootModuleId = $rootModuleId !== null ? new Uuid($rootModuleId) : null;
        $this->sortOrder = $sortOrder;
        $this->isActive = $isActive;
    }
}
