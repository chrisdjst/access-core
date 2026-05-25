# Changelog

All notable changes to `modularize-rbac/core` are documented here. Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [SemVer](https://semver.org/).

## [1.9.0] - 2026-05-25

### Added

- **Soft-delete** on `Role` and `Language` aggregates:
  - `deletedAt()` + `isDeleted()` accessors, plus `softDelete(Clock)` and `restore(Clock)` methods (idempotent).
  - `Role::reconstitute()` accepts a trailing `?DateTimeImmutable $deletedAt`. Same for `Language` constructor.
- **`RestoreRole`** use-case (`admin.roles.delete`) reverses a soft delete. Throws `NotFound` for unknown id, `InvalidInput` when the role isn't soft-deleted.
- **`DeleteRole`** semantic shift: now calls `$role->softDelete()` + `$roles->softDelete($role)` instead of hard delete. System roles and roles with bindings are still refused with the same error messages. Hosts that need true purge keep using the port's `delete()` method directly.
- **`RoleRepository`** port additions (additive — implementors must add):
  - `softDelete(Role)` — set `deletedAt` non-null.
  - `restore(Role)` — clear it.
  - `findIncludingTrashed(Uuid): ?Role` — look up soft-deleted rows.
- **`RoleOutput`** exposes `deletedAt` as the trailing field.
- **`PermissionActionRegistry`** for custom permission actions beyond the 5 CRUD:
  - Pre-seeded with `ModulePermission::FLAG_TO_ACTION`. Hosts register extras via `$registry->register('is_export_allowed', 'export')`.
  - Validates flag names match `/is_[a-z0-9_]+_allowed/` and action names match `/[a-z][a-z0-9_]*/`.
- **`ModulePermission`** carries an optional `extraFlags` array (custom is_xxx_allowed flags), exposed via `extraFlags()` accessor. Default `[]` preserves v1.8 behavior.
- **`PermissionFlagResolver`** constructor now accepts a `PermissionActionRegistry` (default = built-in 5). Methods consult the registry so custom actions are honored end-to-end.

### Tests

- 14 new scenarios across `RestoreRoleTest` (5) and `PermissionActionRegistryTest` (9).

### Backward compat

- `ModulePermission::FLAG_TO_ACTION` const stays for the deprecation cycle. v3.0 will remove it.

## [1.8.0] - 2026-05-25

### Added

- `Application\Shared\Pagination`: limit/offset value object with `DEFAULT_LIMIT = 50`, `MAX_LIMIT = 1000`. Rejects out-of-range values at construction.
- `Application\Shared\PaginatedResult<T>`: items + total + pagination cursor returned by paginated use-cases so HTTP adapters can serialize the envelope without re-counting.
- `Application\Module\ModuleFilter`: isActive, rootModuleId, slugLike (case-insensitive substring).
- `Application\Role\RoleFilter`: guard, tenantId + tenantPresent (split lets callers distinguish "global only" from "any tenant"), isSystem, levelMin/Max with cross-validation, hasParent.
- `ModuleRepository::searchPaginated(ModuleFilter, Pagination): PaginatedResult` port method. Implementors must add this; in-memory double + Eloquent adapter ship implementations.
- `RoleRepository::searchPaginated(RoleFilter, Pagination): PaginatedResult` port method.
- `ListModulesPaginated` use-case (`admin.modules.view`). Returns `PaginatedResult<ModuleOutput>`.
- `ListRolesPaginated` use-case (`admin.roles.view`). Returns `PaginatedResult<RoleOutput>`.

### Tests

- 13 new scenarios across `ListModulesPaginatedTest` and `ListRolesPaginatedTest`.

## [1.7.0] - 2026-05-24

### Added

- `Role.parentRoleId` (`?Uuid`): roles can now reference an ancestor. Stored on the aggregate and exposed via `Role::parentRoleId()`. `Role::create()` rejects self-parenting.
- `Role::create()` / `Role::reconstitute()` accept an optional trailing `parentRoleId` parameter. Existing call sites stay source-compatible.
- `CreateRoleInput` accepts an optional `parent_role_id` string. `CreateRole` use-case validates that the referenced role exists (otherwise throws `InvalidInput`).
- `CloneRole` carries the source role's `parentRoleId` into the clone.
- `RoleOutput` exposes `parentRoleId` as the new trailing field.
- `RoleRepository::resolveAncestors(Uuid $roleId): list<Uuid>` port method: walks the parent chain, immediate parent first, root last. Implementations must short-circuit on cycles and orphan pointers.
- `InMemoryRoleRepository::resolveAncestors()` test double + 3 scenarios.

### Tests

- 9 new scenarios across `RoleHierarchyTest`, `CreateRoleParentTest`, and `ResolveAncestorsTest`.

## [1.6.0] - 2026-05-24

### Added

- `PermissionInheritanceResolver` domain service: pure-function walk of the module hierarchy that resolves whether an ability is allowed considering ancestor bindings. Caller-supplied callables provide flag lookup + parent lookup, so the resolver stays free of persistence. Includes a defensive cycle break (a malformed parent chain stops the walk rather than spinning).
- 8 unit tests covering direct grant, denial, one/multi-level inheritance, sibling grants, cycle safety.

### Notes

- Inheritance is opt-in by host design — hosts that don't construct the resolver keep the legacy "binding must live on the requested module" semantic.

## [1.5.0] - 2026-05-24

### Added

- `BulkCreateModules` use-case (`admin.modules.create`): create many modules in one transaction. Reuses `CreateModuleInput` so each entry inherits the per-entry validation rules (slug format, non-empty name, parent existence). Adds intra-payload slug-uniqueness check and all-or-nothing semantics — domain events fire only after commit.
- `BulkDeleteModules` use-case (`admin.modules.delete`): soft-delete many modules in one transaction. Validates UUIDs + de-duplicates at the input layer; throws `NotFound` for the first missing id and rolls back the whole batch.
- `AssignUsersToRole` use-case (`admin.roles.update`): bind a set of users to a single role atomically. Idempotent — repeated assigns for the same tuple collapse to a no-op via the port contract.
- New port: `UserRoleAssigner` (write-side counterpart to `UserRoleResolver`). Hosts implement `assign(Uuid $roleId, Uuid $userId, ?Uuid $tenantId): void` against their `role_user` pivot. Idempotent by contract.
- New test double: `InMemoryUserRoleAssigner` records every tuple and enforces idempotence in tests.

### Tests

- 16 new use-case scenarios across `BulkCreateModulesTest`, `BulkDeleteModulesTest`, `AssignUsersToRoleTest`.

## [1.4.0] - 2026-05-24

### Added

- `CloneRole` use-case (`admin.roles.create`): produces a new role with the same module-permission matrix as an existing one. Guard, tenant, and level are inherited from the source; `isSystem` is always `false` on the clone. Each source binding is mirrored as a fresh `ModulePermission` + `RoleModulePermission` pair, the external (Spatie) gateway is fed one `applyPlan` per cloned binding, and one `RolePermissionsChanged` event is dispatched per affected module (grants only). 10 use-case scenarios covered in `tests/Application/Role/CloneRoleTest.php`.

## [1.3.0] - 2026-05-24

### Added

- `AuditRepository::deleteOlderThan(DateTimeImmutable $cutoff): int` port method for retention policies. Returns the number of rows removed. Additive — adapters need to implement it (in-memory double + Eloquent adapter on the bridge side).
- `InMemoryAuditRepository::deleteOlderThan()` + 4 unit tests covering: strict cutoff semantics, no-op on past cutoffs, full purge, empty-state assertion.

### Changed

- `AuditRepository` PHP-doc loosened: writes are still append-only via `save()`, but `deleteOlderThan()` is the documented exception. The legacy "never deleted" wording was wrong.

## [1.2.0] - 2026-05-23

### Added

- `CreateRole` use-case (`admin.roles.create`): factory for new roles, enforces (name, guard, tenantId) uniqueness, validates name format.
- `DeleteRole` use-case (`admin.roles.delete`): refuses deletion of system roles and roles that still hold module-permission bindings (caller must drop bindings via `SyncRoleModules` with an empty `modules` array first).
- `RoleRepository::delete(Role)` and `RoleRepository::findByName(name, guard, tenantId)` port methods. Additive — adapters that previously implemented the port need to add these two methods (and any in-memory doubles).
- Tests: 11 new use-case scenarios (`CreateRoleTest`, `DeleteRoleTest`).

## [1.1.1] - 2026-05-23

### Added
- Governance docs: `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `SECURITY.md`, `.github/PULL_REQUEST_TEMPLATE.md`.

## [1.1.0] - 2026-05-23

Additive feature release.

### Added
- `TenantContext` port (`Application/Ports/TenantContext`).
- `Audit` domain: `AuditEntry` entity, `AuditEventName` value object.
- `AuditRepository` port + `AuditQuery` DTO.
- `ListAuditEntries` use-case with paginated output.
- `UserRoleResolver` port.
- Read models: `GetRolePermissionMatrix`, `ListUserAccessibleModules`.
- `RoleModulePermissionRepository::matrixFor()` port method.

## [1.0.0] - 2026-05-23

First publishable Packagist release. This is the framework-agnostic heart of the hexagonal refactor of the legacy `casamento/rbac` package; the Laravel-specific bridge lives at [`modularize-rbac/laravel`](https://github.com/chrisdjst/access-laravel).

### Added

#### Domain layer
- **Value objects**: `Uuid`, `ModuleSlug`, `LanguageCode`, `GuardName`, `PermissionName`, `RoleLevel`. All immutable, self-validating, throwing `InvalidInput` on malformed input.
- **Entities** (each with a `create()` factory and `reconstitute()` hydrator):
  - `Module` (aggregate root, records `ModuleCreated/Updated/Deleted` events, soft-delete)
  - `Role`, `Permission`, `Language`, `Translation`
  - `ModulePermission` (flag set + canonical `FLAG_TO_ACTION` map)
  - `ModulePrice`
  - `RoleModulePermission` (join)
- **Domain services** (pure functions, no I/O):
  - `PermissionFlagResolver` â€” flag set â†’ canonical action names
  - `RoleModulePermissionSynchronizer` â€” computes grant/revoke diff for a role's effective permissions on a module; **preserves non-managed actions** (manage/sign/approve/import/export)
  - `TranslationResolver` â€” locale-fallback resolution (requested â†’ default â†’ raw attribute)
- **Domain events**: `ModuleCreated`, `ModuleUpdated`, `ModuleDeleted`, `RolePermissionsChanged`, `LanguageDefaultChanged`
- **Shared**: `Clock` (port), `IdGenerator` (port), `DomainEvent` interface, `RecordsEvents` trait

#### Application layer
- **Ports**: `ModuleRepository`, `RoleRepository`, `PermissionRepository`, `LanguageRepository`, `TranslationRepository`, `RoleModulePermissionRepository`, `UnitOfWork`, `DomainEventDispatcher`, `LocaleResolver`, `Authorizer`, `ExternalPermissionGateway`
- **Use-cases** (one per legacy controller action):
  - Module: `CreateModule`, `UpdateModule`, `DeleteModule`, `ListModules`, `ShowModule`
  - Role: `ListRoles`, `ShowRole`, `UpdateRole`, `SyncRoleModules`
  - Language: `ListLanguages`, `ShowLanguage`, `CreateLanguage`, `UpdateLanguage`, `DeleteLanguage`, `SetDefaultLanguage`
- Each use-case has immutable Input / Output DTOs

#### Exceptions
- `InvalidInput` (with field name) for 422 mapping at the boundary
- `NotFound` for 404 mapping
- `AuthorizationFailed` for 403 mapping

#### Tooling
- PHP 8.2/8.3/8.4 CI matrix
- PHPStan level 8 clean
- 115 Pest tests, 188 assertions, ~0.4s runtime

### Out of scope for v1.0

This package owns the domain + application layer **only**. Persistence, HTTP, authorization, locale resolution, and event dispatch are all delegated to the host's adapters. The Laravel bridge (`modularize-rbac/laravel`) demonstrates a complete implementation.
