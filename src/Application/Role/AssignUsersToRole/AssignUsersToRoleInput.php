<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\AssignUsersToRole;

use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Input contract for {@see AssignUsersToRole}. Validates UUIDs at
 * construction time and de-duplicates the list — repeated assigns
 * for the same user_id within one payload collapse to a single
 * port call.
 */
final readonly class AssignUsersToRoleInput
{
    public Uuid $roleId;
    /** @var list<Uuid> */
    public array $userIds;
    public ?Uuid $tenantId;

    /**
     * @param  list<string>  $userIds
     */
    public function __construct(string $roleId, array $userIds, ?string $tenantId = null)
    {
        $this->roleId = new Uuid($roleId);

        if ($userIds === []) {
            throw InvalidInput::of('user_ids', 'At least one user_id is required.');
        }

        $seen = [];
        $ids = [];
        foreach ($userIds as $i => $raw) {
            if (! is_string($raw)) {
                throw InvalidInput::of("user_ids.{$i}", 'Each user_id must be a string.');
            }
            $uuid = new Uuid($raw);
            if (isset($seen[$uuid->value])) {
                continue;
            }
            $seen[$uuid->value] = true;
            $ids[] = $uuid;
        }
        $this->userIds = $ids;
        $this->tenantId = $tenantId !== null ? new Uuid($tenantId) : null;
    }
}
