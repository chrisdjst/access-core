<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\Shared;

/**
 * Port for generating fresh identifiers. The domain depends only on
 * {@see Uuid} as a value object; concrete generation (e.g. UUIDv4,
 * UUIDv7, ULID-cast-to-uuid) lives behind this interface.
 */
interface IdGenerator
{
    public function nextUuid(): Uuid;
}
