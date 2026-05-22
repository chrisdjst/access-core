# modularize/access-core

Framework-agnostic core for modular RBAC: **modules + roles + permissions + i18n translations** in pure PHP 8.2+.

> **Status: WIP — scaffold only.** Domain layer lands in PR 1, application layer + ports in PR 2. First Packagist release will be `v1.0.0`.

## What it is

`access-core` is the framework-agnostic heart of the `modularize/access-*` family. It contains:

- **Domain layer** — entities (`Module`, `Role`, `Permission`, `Language`, `Translation`, `RoleModulePermission`...), value objects, domain services (`PermissionFlagResolver`, `TranslationResolver`, `RoleModulePermissionSynchronizer`).
- **Application layer** — use-cases (`CreateModule`, `SyncRoleModules`...) and ports (`ModuleRepository`, `UnitOfWork`, `Clock`, `IdGenerator`, `LocaleResolver`, `Authorizer`, `DomainEventDispatcher`, `ExternalPermissionGateway`).

It contains **no infrastructure** — no Eloquent, no HTTP, no Spatie, no framework dependency. Drop it into any PHP project (Laravel, Symfony, Slim, plain PHP) and provide adapters for the ports.

## Adapters

- **Laravel** → [`modularize/access-laravel`](https://github.com/chrisdjst/access-laravel) — Eloquent repositories, HTTP controllers, migrations, optional Spatie integration.
- Other framework adapters welcome.

## Install (once v1.0 is published)

```bash
composer require modularize/access-core:^1.0
```

## Architecture

```
src/
├── Domain/
│   ├── Shared/                   # Uuid, Clock (port), IdGenerator (port), DomainEvent
│   ├── Module/                   # Module entity, ModuleSlug VO, ModuleRepository port
│   ├── Role/                     # Role entity, GuardName VO, RoleLevel VO, RoleRepository port
│   ├── Permission/               # Permission entity, PermissionName VO, PermissionRepository port
│   ├── RoleModulePermission/     # RoleModulePermission entity + PermissionFlagResolver + RoleModulePermissionSynchronizer (domain services)
│   ├── Translation/              # Language, Translation entities, TranslationResolver service, repositories
│   └── Events/                   # Domain events: ModuleCreated, RolePermissionsChanged, ...
├── Application/
│   ├── Module/                   # CreateModule, UpdateModule, DeleteModule, ListModules, ShowModule use-cases
│   ├── Role/                     # ListRoles, ShowRole, UpdateRole, SyncRoleModules
│   ├── Language/                 # CRUD + SetDefaultLanguage
│   └── Ports/                    # UnitOfWork, DomainEventDispatcher, LocaleResolver, Authorizer, ExternalPermissionGateway
└── Exceptions/
    ├── InvalidInput.php
    ├── NotFound.php
    └── AuthorizationFailed.php
```

## Roadmap

See [CHANGELOG.md](./CHANGELOG.md).

- [x] PR 0: repo scaffold, composer.json, CI
- [ ] PR 1: Domain layer (entities + value objects + domain services + events)
- [ ] PR 2: Application layer (use-cases + ports + in-memory adapters for testing)
- [ ] v1.0.0 release on Packagist (alongside `modularize/access-laravel`)
