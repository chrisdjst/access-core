<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Role\UpdateRole;

use Modularize\Access\Domain\Shared\Uuid;

/**
 * Input for {@see UpdateRole}. Only `displayName` is mutable through
 * this use-case — `name`, `guard`, `tenantId`, `level`, and the
 * `isSystem` flag are seeded by the host and shouldn't drift from
 * what was committed.
 *
 * `displayName === null` clears the override and falls back to `name`.
 */
final readonly class UpdateRoleInput
{
    public Uuid $id;
    public ?string $displayName;

    public function __construct(string $id, ?string $displayName)
    {
        $this->id = new Uuid($id);
        $this->displayName = $displayName !== null && trim($displayName) === ''
            ? null
            : $displayName;
    }
}
