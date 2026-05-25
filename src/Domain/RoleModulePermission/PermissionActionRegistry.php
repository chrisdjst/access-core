<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Domain\RoleModulePermission;

use ModularizeRbac\Core\Domain\Module\ModulePermission;
use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Registry mapping persisted boolean flag names to the
 * Spatie-style action names they unlock. Hosts that need more than
 * the five canonical CRUD actions (list/view/create/update/delete)
 * extend this in their ServiceProvider:
 *
 *     $registry = $app->make(PermissionActionRegistry::class);
 *     $registry->register('is_export_allowed', 'export');
 *
 * `register()` is the only mutation hook; the rest is a read view
 * over the combined (built-in + extension) pairs.
 *
 * The built-in pairs are loaded from {@see ModulePermission::FLAG_TO_ACTION}
 * so the legacy const stays the source of truth for the canonical
 * five. Hosts that disable a built-in must do so by overriding the
 * registry instance entirely (binding their own subclass) — the
 * built-ins are intentionally non-removable to preserve the package
 * contract.
 */
final class PermissionActionRegistry
{
    /** @var array<string, string>  flag name → action name */
    private array $pairs;

    /**
     * @param  array<string, string>  $extras  flag name → action name,
     *         applied on top of the built-in five. Pre-seeded extras
     *         are the same as if {@see self::register()} were called
     *         for each pair after construction.
     */
    public function __construct(array $extras = [])
    {
        $this->pairs = ModulePermission::FLAG_TO_ACTION;
        foreach ($extras as $flag => $action) {
            $this->register($flag, $action);
        }
    }

    public function register(string $flag, string $action): void
    {
        if ($flag === '' || $action === '') {
            throw InvalidInput::of('permission_action', 'Flag and action names cannot be empty.');
        }
        if (preg_match('/^is_[a-z0-9_]+_allowed$/', $flag) !== 1) {
            throw InvalidInput::of(
                'permission_action.flag',
                "Flag name must match /is_[a-z0-9_]+_allowed/. Got: {$flag}"
            );
        }
        if (preg_match('/^[a-z][a-z0-9_]*$/', $action) !== 1) {
            throw InvalidInput::of(
                'permission_action.action',
                "Action name must match /[a-z][a-z0-9_]*/. Got: {$action}"
            );
        }

        $this->pairs[$flag] = $action;
    }

    /**
     * @return array<string, string>  flag → action (full set, native + extras)
     */
    public function all(): array
    {
        return $this->pairs;
    }

    /**
     * @return list<string>  action names in registration order
     */
    public function actions(): array
    {
        return array_values($this->pairs);
    }

    /**
     * @return list<string>  flag names in registration order
     */
    public function flags(): array
    {
        return array_keys($this->pairs);
    }
}
