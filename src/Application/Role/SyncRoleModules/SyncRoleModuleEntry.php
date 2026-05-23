<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\SyncRoleModules;

use ModularizeRbac\Core\Domain\Shared\Uuid;

final readonly class SyncRoleModuleEntry
{
    public function __construct(
        public Uuid $moduleId,
        public bool $isListingAllowed,
        public bool $isReadingAllowed,
        public bool $isWritingAllowed,
        public bool $isEditingAllowed,
        public bool $isDeleteAllowed,
    ) {
    }
}
