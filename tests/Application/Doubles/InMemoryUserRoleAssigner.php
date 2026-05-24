<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Tests\Application\Doubles;

use ModularizeRbac\Core\Application\Ports\UserRoleAssigner;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * Records every (roleId, userId, tenantId) tuple the use-case asks
 * to assign. Idempotent: re-assigning the same tuple is a no-op.
 */
final class InMemoryUserRoleAssigner implements UserRoleAssigner
{
    /** @var list<array{roleId: string, userId: string, tenantId: ?string}> */
    public array $assignments = [];

    public function assign(Uuid $roleId, Uuid $userId, ?Uuid $tenantId = null): void
    {
        foreach ($this->assignments as $row) {
            if ($row['roleId'] === $roleId->value
                && $row['userId'] === $userId->value
                && $row['tenantId'] === $tenantId?->value
            ) {
                return;
            }
        }
        $this->assignments[] = [
            'roleId' => $roleId->value,
            'userId' => $userId->value,
            'tenantId' => $tenantId?->value,
        ];
    }
}
