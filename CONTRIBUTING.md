# Contributing to `modularize-rbac/core`

Thanks for your interest. This package is the framework-agnostic heart of the `modularize-rbac/*` family. Contributions of bug fixes, performance improvements, additional ports, and documentation are welcome.

## Before you start

- Read the [README](README.md) to understand the layering (Domain / Application / Ports).
- Skim the [CHANGELOG](CHANGELOG.md) for recent releases.
- Check the [open issues](https://github.com/chrisdjst/access-core/issues) — your idea may be already discussed.

## Development workflow

```bash
git clone git@github.com:chrisdjst/access-core.git
cd access-core
composer install

# run the test suite
composer test

# static analysis (PHPStan level 8)
composer stan
```

The test suite is **pure PHP** — no framework, no database. It runs in under a second.

## Branch naming

- `feat/<short-description>` — new features
- `fix/<short-description>` — bug fixes
- `chore/<short-description>` — refactors, docs, tooling
- `pr/v<release>-<topic>` — multi-PR roadmap branches (used for big refactors)

## Commit messages

Conventional Commits, optionally scoped:

```
feat(domain): add PermissionInheritanceResolver
fix(audit): handle null tenantId in mapper
chore(ci): cache composer downloads
docs(readme): clarify port responsibilities
```

The first line is < 72 chars. Body explains the *why*, not the *what*.

## Pull requests

- Open against `main`.
- Include a `## Summary` and `## Test plan` section (see `.github/PULL_REQUEST_TEMPLATE.md`).
- Keep diff focused; split unrelated changes into separate PRs.
- Tests are required for new domain logic + use-cases. PHPStan must stay clean.
- If you introduce a breaking change, justify it and update `CHANGELOG.md` under `Unreleased > Breaking`.

## What goes in this package vs the Laravel bridge

This package is **framework-agnostic**. Code that depends on Laravel (Eloquent, container, facades, HTTP, console) belongs in [`modularize-rbac/laravel`](https://github.com/chrisdjst/access-laravel).

Acceptable here:
- Domain entities, value objects, domain services, domain events
- Application use-cases, ports (interfaces), DTOs
- Pure PHP exceptions

Not acceptable here:
- `use Illuminate\…`
- Anything in `vendor/laravel/…`
- `config()`, `app()`, `now()` global helpers
- `DateTimeImmutable::createFromFormat` is fine; `Carbon::now()` is not

## Releasing

Maintainers tag releases via `git tag -a vX.Y.Z` + `gh release create`. The Packagist webhook auto-updates.

Versioning follows SemVer:
- Major: breaking changes to public domain or port contracts
- Minor: additive features (new ports, new use-cases, new value objects)
- Patch: bug fixes, doc-only changes

## Questions

Open a [Discussion](https://github.com/chrisdjst/access-core/discussions) or file an issue. Security reports — see [SECURITY.md](SECURITY.md).
