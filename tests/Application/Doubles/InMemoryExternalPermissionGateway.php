<?php

declare(strict_types=1);

namespace Modularize\Access\Tests\Application\Doubles;

use Modularize\Access\Application\Ports\ExternalPermissionGateway;
use Modularize\Access\Domain\Permission\PermissionName;
use Modularize\Access\Domain\Role\GuardName;
use Modularize\Access\Domain\Shared\Uuid;

/**
 * In-memory stand-in for the Spatie gateway. Holds a map of
 * (roleId, guard) -> set of permission names. Applying a plan
 * mutates the set; tests assert against `permissionsHeldBy()`.
 */
final class InMemoryExternalPermissionGateway implements ExternalPermissionGateway
{
    /** @var array<string, list<string>> */
    private array $holdings = [];

    /** @var list<array{role_id: string, guard: string, granted: list<string>, revoked: list<string>}> */
    public array $applyLog = [];

    public function permissionsHeldBy(Uuid $roleId, GuardName $guard): array
    {
        $key = $this->key($roleId, $guard);
        $names = $this->holdings[$key] ?? [];

        return array_map(fn (string $n) => new PermissionName($n), $names);
    }

    public function applyPlan(Uuid $roleId, GuardName $guard, array $granted, array $revoked): void
    {
        $key = $this->key($roleId, $guard);
        $current = $this->holdings[$key] ?? [];
        $set = array_flip($current);

        foreach ($granted as $name) {
            $set[$name->value] = true;
        }
        foreach ($revoked as $name) {
            unset($set[$name->value]);
        }

        $this->holdings[$key] = array_values(array_keys($set));
        $this->applyLog[] = [
            'role_id' => $roleId->value,
            'guard' => $guard->value,
            'granted' => array_map(fn ($n) => $n->value, $granted),
            'revoked' => array_map(fn ($n) => $n->value, $revoked),
        ];
    }

    public function seed(Uuid $roleId, GuardName $guard, string ...$permissionNames): void
    {
        $this->holdings[$this->key($roleId, $guard)] = array_values($permissionNames);
    }

    private function key(Uuid $roleId, GuardName $guard): string
    {
        return $roleId->value.'|'.$guard->value;
    }
}
