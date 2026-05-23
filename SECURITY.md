# Security Policy

## Supported versions

| Version | Status |
|---|---|
| 1.1.x | Maintained — security fixes and bug patches |
| 1.0.x | Security fixes only until 2027-05 |
| < 1.0 | Not supported (pre-Packagist) |

## Reporting a vulnerability

Please **do not** open a public GitHub issue for security reports.

Instead, report privately by emailing **christophertheilacher@gmail.com** with:

- A description of the issue and where it lives in the code.
- Steps to reproduce (proof-of-concept welcome — keep it minimal).
- The version(s) affected.
- Any suggested mitigation, if you have one.

You will get an acknowledgement within 72 hours. Once the report is triaged we'll coordinate disclosure: a fixed release goes out, then a public advisory + CVE if appropriate.

## What counts as a security issue

- Authorization bypass — a path that lets an actor obtain permissions they shouldn't.
- Tenant boundary leakage — data from one tenant exposed to another.
- Injection vectors via input passed through value objects.
- Audit log forgery or tampering.

## What doesn't

- Misconfiguration in a host application using the package (configure your guards and middleware correctly).
- Bugs that require an attacker to already have admin privileges in the host.
- Issues in `spatie/laravel-permission` itself — report those upstream.
