<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\BulkDeleteModules;

use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Input contract for {@see BulkDeleteModules}. Validates that every
 * id is a well-formed UUID at construction time so the use-case can
 * fail fast before touching the repository.
 */
final readonly class BulkDeleteModulesInput
{
    /** @var list<Uuid> */
    public array $ids;

    /**
     * @param  list<string>  $rawIds
     */
    public function __construct(array $rawIds)
    {
        if ($rawIds === []) {
            throw InvalidInput::of('ids', 'Bulk module delete payload cannot be empty.');
        }

        $ids = [];
        $seen = [];
        foreach ($rawIds as $i => $raw) {
            if (! is_string($raw)) {
                throw InvalidInput::of("ids.{$i}", 'Each id must be a string.');
            }
            $uuid = new Uuid($raw);
            if (isset($seen[$uuid->value])) {
                throw InvalidInput::of("ids.{$i}", "Duplicate id within payload: {$uuid->value}");
            }
            $seen[$uuid->value] = true;
            $ids[] = $uuid;
        }

        $this->ids = $ids;
    }
}
