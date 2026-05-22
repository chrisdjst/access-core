<?php

declare(strict_types=1);

namespace Modularize\Access\Tests\Application\Doubles;

use Modularize\Access\Application\Ports\ModuleRepository;
use Modularize\Access\Domain\Module\Module;
use Modularize\Access\Domain\Module\ModuleSlug;
use Modularize\Access\Domain\Shared\Uuid;

final class InMemoryModuleRepository implements ModuleRepository
{
    /** @var array<string, Module> */
    private array $byId = [];

    public function find(Uuid $id): ?Module
    {
        return $this->byId[$id->value] ?? null;
    }

    public function findBySlug(ModuleSlug $slug): ?Module
    {
        foreach ($this->byId as $module) {
            if ($module->slug()->equals($slug) && ! $module->isDeleted()) {
                return $module;
            }
        }

        return null;
    }

    public function allActiveTree(): array
    {
        $result = [];
        foreach ($this->byId as $module) {
            if (! $module->isDeleted()) {
                $result[] = $module;
            }
        }
        usort($result, function (Module $a, Module $b): int {
            $rootA = $a->rootModuleId()?->value;
            $rootB = $b->rootModuleId()?->value;
            if ($rootA !== $rootB) {
                if ($rootA === null) {
                    return -1;
                }
                if ($rootB === null) {
                    return 1;
                }

                return $rootA <=> $rootB;
            }

            return $a->sortOrder() <=> $b->sortOrder();
        });

        return $result;
    }

    public function save(Module $module): void
    {
        $this->byId[$module->id->value] = $module;
    }
}
