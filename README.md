# modularize/access-core

Framework-agnostic core for modular RBAC: **modules + roles + permissions + i18n translations** in pure PHP 8.2+.

[![CI](https://github.com/chrisdjst/access-core/actions/workflows/ci.yml/badge.svg)](https://github.com/chrisdjst/access-core/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/modularize/access-core.svg)](https://packagist.org/packages/modularize/access-core)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

## What it is

`access-core` is the framework-agnostic heart of the `modularize/access-*` family — pure PHP with zero framework dependency. It contains:

- **Domain layer** — entities (`Module`, `Role`, `Permission`, `Language`, `Translation`, `RoleModulePermission`, ...), immutable value objects (`Uuid`, `ModuleSlug`, `LanguageCode`, `GuardName`, `PermissionName`, `RoleLevel`), domain services (`PermissionFlagResolver`, `TranslationResolver`, `RoleModulePermissionSynchronizer`), domain events.
- **Application layer** — use-cases (`CreateModule`, `UpdateModule`, `SyncRoleModules`, `SetDefaultLanguage`, ...) and the ports the host must implement: `*Repository`, `UnitOfWork`, `Clock`, `IdGenerator`, `LocaleResolver`, `Authorizer`, `DomainEventDispatcher`, `ExternalPermissionGateway`.

Drop it into any PHP project, implement the ports with whatever ORM / HTTP / event bus you use, and the use-cases run.

## Why a separate package

A clean port boundary between domain logic and Laravel-specific infrastructure:

- **SOLID**: each use-case has one job; the synchronizer is testable without a DB; ports are small and focused.
- **Reusable**: the same use-cases run from Laravel, Symfony, Slim, or plain-PHP CLI.
- **Testable**: 115 unit + application tests run in ~0.4s with no framework, no DB.

## Install

```bash
composer require modularize/access-core
```

PHP 8.2+. No other runtime dependency.

## Existing adapters

- **Laravel**: [`modularize/access-laravel`](https://github.com/chrisdjst/access-laravel) — Eloquent repositories, HTTP controllers, FormRequests, migrations, optional Spatie integration.
- *Symfony, Slim, plain PHP*: not shipped today; PRs welcome.

## Quick example (wiring use-cases by hand)

```php
use Modularize\Access\Application\Module\CreateModule\CreateModule;
use Modularize\Access\Application\Module\CreateModule\CreateModuleInput;
use Modularize\Access\Tests\Application\Doubles\{
    InMemoryModuleRepository,
    AllowingAuthorizer,
    PassthroughUnitOfWork,
    RecordingEventDispatcher,
    SequentialIdGenerator,
    FixedClock,
};

$create = new CreateModule(
    modules: new InMemoryModuleRepository(),
    authorizer: new AllowingAuthorizer(),
    uow: new PassthroughUnitOfWork(),
    events: new RecordingEventDispatcher(),
    ids: new SequentialIdGenerator(),
    clock: FixedClock::at('2026-05-22T00:00:00Z'),
);

$module = $create->execute(new CreateModuleInput(
    slug: 'events',
    name: 'Events',
    redirect: '/events',
    icon: 'calendar',
    rootModuleId: null,
    sortOrder: 10,
));

echo $module->id; // UUID
```

In real apps, replace the in-memory doubles with adapters that talk to your DB / event bus / authz system.

## Architecture

```
src/
├── Domain/
│   ├── Shared/                   # Uuid, Clock (port), IdGenerator (port), DomainEvent
│   ├── Module/                   # Module entity, ModuleSlug VO, ModulePermission entity
│   ├── Role/                     # Role entity, GuardName VO, RoleLevel VO
│   ├── Permission/               # Permission entity, PermissionName VO
│   ├── RoleModulePermission/     # RoleModulePermission entity + domain services
│   ├── Translation/              # Language, Translation entities, TranslationResolver
│   └── Events/                   # ModuleCreated, RolePermissionsChanged, ...
├── Application/
│   ├── Module/                   # CreateModule, UpdateModule, DeleteModule, ListModules, ShowModule
│   ├── Role/                     # ListRoles, ShowRole, UpdateRole, SyncRoleModules
│   ├── Language/                 # ListLanguages, ShowLanguage, CreateLanguage, UpdateLanguage, DeleteLanguage, SetDefaultLanguage
│   └── Ports/                    # *Repository, UnitOfWork, LocaleResolver, Authorizer, ...
└── Exceptions/                   # InvalidInput, NotFound, AuthorizationFailed
```

## Ports you need to implement

For a host to use this package, it must provide implementations of these interfaces (defined in `src/Application/Ports/`):

| Port | What it does |
|---|---|
| `ModuleRepository` | Persist/lookup `Module` aggregates |
| `RoleRepository` | Persist/lookup `Role` aggregates |
| `PermissionRepository` | Persist/lookup `Permission` aggregates |
| `LanguageRepository` | Persist/lookup `Language` aggregates |
| `TranslationRepository` | Persist/lookup `Translation` rows by polymorphic owner |
| `RoleModulePermissionRepository` | Persist/lookup role-module binding rows |
| `UnitOfWork` | Wrap a closure in a transaction |
| `Clock` | Read "now" (`DateTimeImmutable`) |
| `IdGenerator` | Mint fresh UUIDs |
| `LocaleResolver` | Current locale + fallback locale |
| `Authorizer` | Resolve actor id; check abilities; throw `AuthorizationFailed` |
| `DomainEventDispatcher` | Forward domain events to the host's bus |
| `ExternalPermissionGateway` | Optional — replicate grants/revokes into an external authz store (e.g. Spatie). A `NullExternalPermissionGateway` is a valid no-op. |

The Laravel adapter implements all of these against Eloquent + Laravel's container.

## Domain services

These pure-function services hold the non-trivial business logic; they're directly callable from any use-case or host code:

- **`PermissionFlagResolver`** — converts the canonical 5-flag boolean tuple (`is_listing_allowed`, `is_reading_allowed`, `is_writing_allowed`, `is_editing_allowed`, `is_delete_allowed`) into Spatie-style action names (`list`, `view`, `create`, `update`, `delete`).
- **`RoleModulePermissionSynchronizer`** — given a module slug + desired flag set + current permissions a role holds, computes the (grant, revoke) plan. **Preserves non-managed permissions** like `manage`/`sign`/`approve` that the host may have added outside this package.
- **`TranslationResolver`** — resolves a field's translation in a target locale, falling back to a configured default locale, then to the raw attribute.

## Test plan

```bash
composer install
composer test    # 115 tests, ~0.4s, no framework
composer stan    # PHPStan level 8
```

## License

MIT — see [LICENSE](./LICENSE).
