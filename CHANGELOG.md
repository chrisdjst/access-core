# Changelog

All notable changes to `modularize/access-core` are documented here. Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [SemVer](https://semver.org/).

## [Unreleased]

### Added (PR 0)
- Initial repository scaffold.
- `composer.json` for `modularize/access-core` (PHP 8.2+, no framework dependencies).
- PSR-4 autoload: `Modularize\Access\` → `src/`.
- Dev dependencies: `pestphp/pest`, `phpstan/phpstan`.
- `LICENSE` (MIT), `.gitattributes`, `.gitignore`.
- CI workflow: PHP 8.2/8.3/8.4 matrix, no framework matrix.

### Planned

- **PR 1** — Domain layer: entities (`Module`, `Role`, `Permission`, `Language`, `Translation`, `ModulePermission`, `ModulePrice`, `RoleModulePermission`), value objects (`Uuid`, `ModuleSlug`, `LanguageCode`, `GuardName`, `PermissionName`, `RoleLevel`), domain services (`PermissionFlagResolver`, `TranslationResolver`, `RoleModulePermissionSynchronizer`), domain events.
- **PR 2** — Application layer: use-cases for Module/Role/Language operations, ports (`*Repository`, `UnitOfWork`, `Clock`, `IdGenerator`, `LocaleResolver`, `Authorizer`, `DomainEventDispatcher`, `ExternalPermissionGateway`), in-memory adapters for testing.
- **v1.0.0** — first Packagist release alongside `modularize/access-laravel`.
