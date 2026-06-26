<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\ResolveModuleVersion;

use ModularizeRbac\Core\Domain\Shared\Uuid;

final readonly class ResolveModuleVersionInput
{
    public Uuid $moduleId;
    public string $subjectType;
    public Uuid $subjectId;
    public int $bucketPct;
    public string $salt;

    public function __construct(
        string $moduleId,
        string $subjectType,
        string $subjectId,
        int $bucketPct = 0,
        string $salt = '',
    ) {
        $this->moduleId = new Uuid($moduleId);
        $this->subjectType = $subjectType;
        $this->subjectId = new Uuid($subjectId);
        $this->bucketPct = max(0, min(100, $bucketPct));
        $this->salt = $salt;
    }
}
