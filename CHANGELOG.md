# Changelog

All notable changes to `modularize-rbac/core` are documented here. Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [SemVer](https://semver.org/).

## [1.0.0] - Unreleased

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
