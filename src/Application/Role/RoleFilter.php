<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role;

use ModularizeRbac\Core\Domain\Role\GuardName;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Optional filter set for {@see \ModularizeRbac\Core\Application\Ports\RoleRepository::searchPaginated()}.
 *
 * - `tenantId === null` AND `tenantPresent === true` means "only
 *   global roles" (tenant column is NULL). `tenantPresent === false`
 *   means "any tenant" (no filter on the column). The split is needed
 *   because the existing non-paginated `search()` port treats `null`
 *   tenantId as "match any" — keeping backward compat there but
 *   surfacing the NULL-vs-any distinction here.
 * - `levelMin` / `levelMax` are inclusive.
 * - `hasParent` filters on whether `parent_role_id` is NULL (false)
 *   or NOT NULL (true).
 */
final readonly class RoleFilter
{
    public ?GuardName $guard;
    public ?Uuid $tenantId;

    public function __construct(
        ?string $guard = null,
        ?string $tenantId = null,
        public bool $tenantPresent = false,
        public ?bool $isSystem = null,
        public ?int $levelMin = null,
        public ?int $levelMax = null,
        public ?bool $hasParent = null,
    ) {
        $this->guard = $guard !== null ? new GuardName($guard) : null;
        $this->tenantId = $tenantId !== null ? new Uuid($tenantId) : null;

        if ($levelMin !== null && $levelMin < 0) {
            throw InvalidInput::of('level_min', 'Level min must be >= 0.');
        }
        if ($levelMax !== null && $levelMax < 0) {
            throw InvalidInput::of('level_max', 'Level max must be >= 0.');
        }
        if ($levelMin !== null && $levelMax !== null && $levelMin > $levelMax) {
            throw InvalidInput::of(
                'level_min',
                'Level min cannot be greater than level max.',
            );
        }
    }
}
