<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\UpdateModule;

use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Input for {@see UpdateModule}. The module's `slug` is immutable —
 * permission names depend on it and renaming would corrupt every
 * role binding. Edit name/redirect/icon/parent/order/active flag.
 */
final readonly class UpdateModuleInput
{
    public Uuid $id;
    public string $name;
    public ?string $redirect;
    public ?string $icon;
    public ?Uuid $rootModuleId;
    public int $sortOrder;
    public bool $isActive;

    public function __construct(
        string $id,
        string $name,
        ?string $redirect,
        ?string $icon,
        ?string $rootModuleId,
        int $sortOrder,
        bool $isActive,
    ) {
        if (trim($name) === '') {
            throw InvalidInput::of('name', 'Module name cannot be empty.');
        }
        if ($sortOrder < 0) {
            throw InvalidInput::of('sort_order', 'Sort order must be >= 0.');
        }

        $this->id = new Uuid($id);
        $this->name = $name;
        $this->redirect = $redirect;
        $this->icon = $icon;
        $this->rootModuleId = $rootModuleId !== null ? new Uuid($rootModuleId) : null;
        $this->sortOrder = $sortOrder;
        $this->isActive = $isActive;
    }
}
