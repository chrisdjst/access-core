<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\DeleteCohortOverride;

use ModularizeRbac\Core\Application\Ports\Authorizer;
use ModularizeRbac\Core\Application\Ports\CohortOverrideRepository;
use ModularizeRbac\Core\Application\Ports\UnitOfWork;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\NotFound;

final class DeleteCohortOverride
{
    public function __construct(
        private readonly CohortOverrideRepository $overrides,
        private readonly Authorizer $authorizer,
        private readonly UnitOfWork $uow,
    ) {
    }

    public function execute(Uuid $overrideId): void
    {
        $this->authorizer->ensure('admin.modules.cohorts.delete');

        if ($this->overrides->find($overrideId) === null) {
            throw NotFound::of('cohort_override', $overrideId->value);
        }

        $this->uow->transactional(fn () => $this->overrides->delete($overrideId));
    }
}
