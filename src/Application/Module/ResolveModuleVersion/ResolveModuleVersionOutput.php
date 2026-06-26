<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\ResolveModuleVersion;

use ModularizeRbac\Core\Application\Module\ModuleVersionData;

final readonly class ResolveModuleVersionOutput
{
    public function __construct(
        public ModuleVersionData $version,
        public string $source, // 'override' | 'hash' | 'stable_fallback'
    ) {
    }
}
