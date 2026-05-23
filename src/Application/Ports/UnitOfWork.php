<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Ports;

use Throwable;

/**
 * Transactional boundary port. The application layer wraps each
 * use-case body in `transactional()`; the bridge adapter (e.g.
 * `LaravelUnitOfWork` wrapping `DB::transaction()`) ensures all
 * repository writes commit together or roll back together.
 *
 * The callable receives no arguments and may return any value; that
 * value is returned by `transactional()` unchanged so use-cases can
 * compose their output naturally.
 */
interface UnitOfWork
{
    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $work
     * @return TReturn
     *
     * @throws Throwable
     */
    public function transactional(callable $work): mixed;
}
