<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use ModularizeRbac\Core\Application\Module\ModuleVersionData;
use ModularizeRbac\Core\Domain\Module\Channel;
use ModularizeRbac\Core\Domain\Module\ModuleVersion;
use ModularizeRbac\Core\Domain\Shared\Uuid;

interface ModuleVersionRepository
{
    public function find(Uuid $id): ?ModuleVersionData;

    public function findByModuleAndVersion(Uuid $moduleId, ModuleVersion $version): ?ModuleVersionData;

    /**
     * Return the active stable version for a module.
     * Returns null when no stable version exists yet.
     */
    public function findStableByModule(Uuid $moduleId): ?ModuleVersionData;

    /**
     * Return the most recently created active version on the given channel, or null.
     */
    public function findLatestByChannel(Uuid $moduleId, Channel $channel): ?ModuleVersionData;

    /**
     * Return all versions for a module ordered by semver descending.
     *
     * @return list<ModuleVersionData>
     */
    public function allByModule(Uuid $moduleId): array;

    public function save(ModuleVersionData $data): void;

    public function delete(Uuid $id): void;
}
