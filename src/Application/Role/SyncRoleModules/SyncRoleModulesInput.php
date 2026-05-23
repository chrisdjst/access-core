<?php

declare(strict_types=1);

namespace ModularizeRbac\Core\Application\Role\SyncRoleModules;

use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\InvalidInput;

/**
 * Input for {@see SyncRoleModules}. The payload mirrors the legacy
 * REST contract: a role id plus a list of module bindings with the
 * five canonical action flags.
 *
 * Each binding is normalized to an immutable {@see SyncRoleModuleEntry}
 * so the use-case body never touches arrays. Duplicate module ids
 * within the payload are rejected at construction.
 */
final readonly class SyncRoleModulesInput
{
    public Uuid $roleId;

    /** @var list<SyncRoleModuleEntry> */
    public array $entries;

    /**
     * @param  list<array{module_id: string, is_listing_allowed?: bool, is_reading_allowed?: bool, is_writing_allowed?: bool, is_editing_allowed?: bool, is_delete_allowed?: bool}>  $modules
     */
    public function __construct(string $roleId, array $modules)
    {
        $this->roleId = new Uuid($roleId);
        $seen = [];
        $entries = [];
        foreach ($modules as $i => $row) {
            if (! isset($row['module_id'])) {
                throw InvalidInput::of("modules.{$i}.module_id", 'Missing module_id.');
            }
            $entry = new SyncRoleModuleEntry(
                moduleId: new Uuid($row['module_id']),
                isListingAllowed: (bool) ($row['is_listing_allowed'] ?? false),
                isReadingAllowed: (bool) ($row['is_reading_allowed'] ?? false),
                isWritingAllowed: (bool) ($row['is_writing_allowed'] ?? false),
                isEditingAllowed: (bool) ($row['is_editing_allowed'] ?? false),
                isDeleteAllowed: (bool) ($row['is_delete_allowed'] ?? false),
            );
            if (isset($seen[$entry->moduleId->value])) {
                throw InvalidInput::of("modules.{$i}.module_id", "Duplicate module_id: {$entry->moduleId->value}");
            }
            $seen[$entry->moduleId->value] = true;
            $entries[] = $entry;
        }
        $this->entries = $entries;
    }
}
