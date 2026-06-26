<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\UpsertCohortOverride;

use ModularizeRbac\Core\Application\Module\CohortOverrideData;
use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\CohortOverrideRepository;
use ModularizeRbac\Core\Application\Ports\ModuleVersionRepository;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\IdGenerator;
use ModularizeRbac\Core\Exceptions\NotFound;

final class UpsertCohortOverride
{
    public function __construct(
        private readonly CohortOverrideRepository $overrides,
        private readonly ModuleVersionRepository $versions,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
        private readonly IdGenerator $ids,
        private readonly Clock $clock,
    ) {
    }

    public function execute(UpsertCohortOverrideInput $input): CohortOverrideData
    {
        $this->authorizer->ensure('admin.modules.cohorts.upsert');

        if ($this->versions->find($input->moduleVersionId) === null) {
            throw NotFound::of('module_version', $input->moduleVersionId->value);
        }

        $now = $this->clock->now();

        // Find existing non-expired override for this subject/module combination
        $existing = $input->subjectId !== null
            ? $this->overrides->findActive($input->moduleId, $input->subjectType, $input->subjectId, $now)
            : null;

        $data = new CohortOverrideData(
            id: $existing?->id ?? $this->ids->nextUuid(),
            moduleId: $input->moduleId,
            moduleVersionId: $input->moduleVersionId,
            subjectType: $input->subjectType,
            subjectId: $input->subjectId,
            reason: $input->reason,
            pinnedBy: $this->authorizer->actorId(),
            pinnedAt: $existing?->pinnedAt ?? $now,
            expiresAt: $input->expiresAt,
        );

        $this->uow->transactional(fn () => $this->overrides->save($data));

        return $data;
    }
}
