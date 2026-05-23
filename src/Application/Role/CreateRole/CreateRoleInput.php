<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\CreateRole;

use ModularizeRbac\Core\Domain\Role\GuardName;
use ModularizeRbac\Core\Domain\Role\RoleLevel;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Input contract for {@see CreateRole}. The HTTP adapter constructs
 * one from a validated FormRequest; CLI callers build one directly.
 */
final readonly class CreateRoleInput
{
    public string $name;
    public ?string $displayName;
    public GuardName $guard;
    public ?Uuid $tenantId;
    public RoleLevel $level;
    public bool $isSystem;

    public function __construct(
        string $name,
        ?string $displayName,
        string $guard,
        ?string $tenantId,
        int $level = 0,
        bool $isSystem = false,
    ) {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw InvalidInput::of('name', 'Role name cannot be empty.');
        }
        if (preg_match('/^[a-z][a-z0-9_-]*$/', $trimmed) !== 1) {
            throw InvalidInput::of(
                'name',
                "Role name must be lowercase snake/kebab-case (matches /[a-z][a-z0-9_-]*/). Got: {$name}"
            );
        }
        $this->name = $trimmed;
        $this->displayName = $displayName !== null && trim($displayName) === ''
            ? null
            : $displayName;
        $this->guard = new GuardName($guard);
        $this->tenantId = $tenantId !== null ? new Uuid($tenantId) : null;
        $this->level = new RoleLevel($level);
        $this->isSystem = $isSystem;
    }
}
