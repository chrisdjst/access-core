<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Shared;

use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Value object representing a windowed cursor over a result set.
 *
 * `limit` is bounded to [1, 1000] to protect the host from
 * accidentally requesting an unbounded page; hosts that legitimately
 * need to dump everything should call the non-paginated `search()`
 * port method instead.
 *
 * `offset` is bounded to [0, PHP_INT_MAX]. Negative offsets throw at
 * construction time. The repository implementations are free to
 * translate offset/limit to native cursor semantics if their driver
 * supports it.
 *
 * The constructor accepts raw ints (typically from query strings) and
 * validates at the boundary — caller-facing code never has to second-
 * guess what `new Pagination(null, null)` means.
 */
final readonly class Pagination
{
    public const DEFAULT_LIMIT = 50;
    public const MAX_LIMIT = 1000;

    public int $limit;
    public int $offset;

    public function __construct(?int $limit = null, ?int $offset = null)
    {
        $resolvedLimit = $limit ?? self::DEFAULT_LIMIT;
        $resolvedOffset = $offset ?? 0;

        if ($resolvedLimit < 1) {
            throw InvalidInput::of('limit', 'Pagination limit must be >= 1.');
        }
        if ($resolvedLimit > self::MAX_LIMIT) {
            throw InvalidInput::of(
                'limit',
                'Pagination limit must be <= '.self::MAX_LIMIT.'.',
            );
        }
        if ($resolvedOffset < 0) {
            throw InvalidInput::of('offset', 'Pagination offset must be >= 0.');
        }

        $this->limit = $resolvedLimit;
        $this->offset = $resolvedOffset;
    }

    public static function default(): self
    {
        return new self();
    }
}
