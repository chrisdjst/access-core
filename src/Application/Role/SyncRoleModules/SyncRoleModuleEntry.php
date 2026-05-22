<?php

declare(strict_types=1);

namespace Modularize\Access\Application\Role\SyncRoleModules;

use Modularize\Access\Domain\Shared\Uuid;

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
