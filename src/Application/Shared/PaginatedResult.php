<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Shared;

/**
 * Container that bundles a windowed result slice with the total count
 * + the cursor that produced it. Use-cases return this directly so
 * the HTTP adapter can serialize the envelope without re-querying
 * the repository for the count.
 *
 * @template T of object
 */
final readonly class PaginatedResult
{
    /**
     * @param  list<T>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public Pagination $pagination,
    ) {
    }
}
