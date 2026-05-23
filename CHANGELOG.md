# Changelog

All notable changes to `modularize-rbac/core` are documented here. Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [SemVer](https://semver.org/).

## [1.3.0] - Unreleased

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
