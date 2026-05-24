<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Module\BulkCreateModules;

use ModularizeRbac\Core\Application\Module\CreateModule\CreateModuleInput;
use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Input contract for {@see BulkCreateModules}.
 *
 * Each entry is normalized into a {@see CreateModuleInput} so the
 * single-create validation rules (slug format, non-empty name,
 * non-negative sort order, parent uuid format) all apply to bulk
 * payloads. The bulk use-case adds atomicity + intra-payload slug
 * uniqueness on top.
 */
final readonly class BulkCreateModulesInput
{
    /** @var list<CreateModuleInput> */
    public array $modules;

    /**
     * @param  list<array<string, mixed>>  $modules  Raw entries; each
     *         entry follows the same key shape as a single
     *         CreateModuleInput constructor call.
     */
    public function __construct(array $modules)
    {
        if ($modules === []) {
            throw InvalidInput::of('modules', 'Bulk module create payload cannot be empty.');
        }

        $normalized = [];
        foreach ($modules as $i => $raw) {
            if (! is_array($raw)) {
                throw InvalidInput::of("modules.{$i}", 'Each entry must be an object.');
            }
            $slug = $raw['slug'] ?? null;
            $name = $raw['name'] ?? null;
            if (! is_string($slug) || ! is_string($name)) {
                throw InvalidInput::of(
                    "modules.{$i}",
                    'Each entry must include string "slug" and "name".',
                );
            }
            $normalized[] = new CreateModuleInput(
                slug: $slug,
                name: $name,
                redirect: isset($raw['redirect']) && is_string($raw['redirect']) ? $raw['redirect'] : null,
                icon: isset($raw['icon']) && is_string($raw['icon']) ? $raw['icon'] : null,
                rootModuleId: isset($raw['root_module_id']) && is_string($raw['root_module_id']) ? $raw['root_module_id'] : null,
                sortOrder: isset($raw['sort_order']) && is_int($raw['sort_order']) ? $raw['sort_order'] : 0,
                isActive: ! isset($raw['is_active']) || (bool) $raw['is_active'],
            );
        }

        // Intra-payload slug uniqueness — catch duplicates before the
        // first row hits the DB, so the whole batch fails fast.
        $seen = [];
        foreach ($normalized as $i => $entry) {
            if (isset($seen[$entry->slug->value])) {
                throw InvalidInput::of(
                    "modules.{$i}.slug",
                    "Duplicate slug within payload: {$entry->slug->value}",
                );
            }
            $seen[$entry->slug->value] = true;
        }

        $this->modules = $normalized;
    }
}
