<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\ResolveModuleVersion;

use ModularizeRbac\Core\Application\Module\CohortOverrideData;
use ModularizeRbac\Core\Application\Module\ModuleVersionData;
use ModularizeRbac\Core\Application\Ports\CohortOverrideRepository;
use ModularizeRbac\Core\Application\Ports\DomainEventDispatcher;
use ModularizeRbac\Core\Application\Ports\ModuleVersionRepository;
use ModularizeRbac\Core\Domain\Events\ModuleExposed;
use ModularizeRbac\Core\Domain\Module\Channel;
use ModularizeRbac\Core\Domain\Module\CohortBucket;
use ModularizeRbac\Core\Domain\Shared\Clock;
use ModularizeRbac\Core\Domain\Shared\IdGenerator;
use ModularizeRbac\Core\Exceptions\NotFound;

/**
 * Resolves which module version a subject should see.
 *
 * Resolution order:
 *   1. Explicit override in module_cohort_overrides (override path)
 *   2. Deterministic hash bucket if a beta version exists (hash path)
 *      — writes a freeze pin on first assignment to prevent flicker
 *   3. Stable version fallback (stable_fallback path)
 *
 * Dispatches ModuleExposed at the configured sample rate.
 * The freeze-pin write is NEVER sampled — it is a correctness operation.
 */
final class ResolveModuleVersion
{
    public function __construct(
        private readonly ModuleVersionRepository $versions,
        private readonly CohortOverrideRepository $overrides,
        private readonly DomainEventDispatcher $events,
        private readonly IdGenerator $ids,
        private readonly Clock $clock,
        private readonly float $exposureSampleRate = 0.01,
    ) {
    }

    public function execute(ResolveModuleVersionInput $input): ResolveModuleVersionOutput
    {
        $now = $this->clock->now();

        // 1. Explicit override
        $override = $this->overrides->findActive(
            $input->moduleId,
            $input->subjectType,
            $input->subjectId,
            $now,
        );

        if ($override !== null) {
            $version = $this->versions->find($override->moduleVersionId);
            if ($version !== null) {
                $this->maybeDispatchExposure($version, $input, 'override', $now);

                return new ResolveModuleVersionOutput($version, 'override');
            }
        }

        // 2. Deterministic hash → beta channel candidate
        if ($input->bucketPct > 0) {
            $beta = $this->versions->findLatestByChannel($input->moduleId, Channel::Beta);
            if ($beta !== null) {
                $bucket = CohortBucket::forSubject($input->moduleId, $input->subjectId, $input->salt);
                if ($bucket->lessThan($input->bucketPct)) {
                    // Freeze-on-first-exposure: write pin so bucket_pct changes don't cause flicker.
                    $this->writeFreezePin($override, $beta, $input, $now);
                    $this->maybeDispatchExposure($beta, $input, 'hash', $now);

                    return new ResolveModuleVersionOutput($beta, 'hash');
                }
            }
        }

        // 3. Stable fallback
        $stable = $this->versions->findStableByModule($input->moduleId);
        if ($stable === null) {
            throw NotFound::of('module_stable_version', $input->moduleId->value);
        }

        $this->maybeDispatchExposure($stable, $input, 'stable_fallback', $now);

        return new ResolveModuleVersionOutput($stable, 'stable_fallback');
    }

    private function writeFreezePin(
        ?CohortOverrideData $existingOverride,
        ModuleVersionData $version,
        ResolveModuleVersionInput $input,
        \DateTimeImmutable $now,
    ): void {
        if ($existingOverride !== null) {
            return; // already pinned — don't overwrite an explicit admin pin
        }

        $pin = new CohortOverrideData(
            id: $this->ids->nextUuid(),
            moduleId: $input->moduleId,
            moduleVersionId: $version->id,
            subjectType: $input->subjectType,
            subjectId: $input->subjectId,
            reason: 'auto-pinned-on-exposure',
            pinnedBy: null,
            pinnedAt: $now,
            expiresAt: null,
        );
        $this->overrides->save($pin);
    }

    private function maybeDispatchExposure(
        ModuleVersionData $version,
        ResolveModuleVersionInput $input,
        string $source,
        \DateTimeImmutable $now,
    ): void {
        if ($this->exposureSampleRate <= 0.0) {
            return;
        }
        if ($this->exposureSampleRate < 1.0 && mt_rand(0, 9999) >= (int) ($this->exposureSampleRate * 10000)) {
            return;
        }

        $this->events->dispatch(new ModuleExposed(
            moduleId: $version->moduleId,
            versionId: $version->id,
            subjectType: $input->subjectType,
            subjectId: $input->subjectId,
            channel: $version->channel,
            resolutionSource: $source,
            occurredAt: $now,
        ));
    }
}
