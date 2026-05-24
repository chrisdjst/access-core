<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\CloneRole;

use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Input contract for {@see CloneRole}.
 *
 * The new role inherits its guard, tenant, and level from the source.
 * Only the identifier (`name`) and the optional human label
 * (`displayName`) need to be supplied. The system flag is intentionally
 * dropped on clone — copying a "super-admin" should yield a regular
 * role, not another protected one.
 */
final readonly class CloneRoleInput
{
    public Uuid $sourceRoleId;
    public string $name;
    public ?string $displayName;

    public function __construct(
        string $sourceRoleId,
        string $name,
        ?string $displayName = null,
    ) {
        $this->sourceRoleId = new Uuid($sourceRoleId);

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
    }
}
