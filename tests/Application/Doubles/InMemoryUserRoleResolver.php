<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Tests\Application\Doubles;

use ModularizeRbac\Core\Application\Ports\UserRoleResolver;
use ModularizeRbac\Core\Domain\Shared\Uuid;

/**
 * In-memory user-role mapping. Tests seed assignments via
 * `assign($userId, $roleId, ...)`. Unknown users return [].
 */
final class InMemoryUserRoleResolver implements UserRoleResolver
{
    /** @var array<string, list<Uuid>> */
    private array $assignments = [];

    public function assign(Uuid $userId, Uuid ...$roleIds): void
    {
        $existing = $this->assignments[$userId->value] ?? [];
        foreach ($roleIds as $roleId) {
            $existing[] = $roleId;
        }
        $this->assignments[$userId->value] = $existing;
    }

    public function roleIdsFor(Uuid $userId): array
    {
        return $this->assignments[$userId->value] ?? [];
    }
}
