# Architecture Foundation

## Scope

This folder defines the enforceable engineering baseline for a Laravel 12 + PHP 8.5 + Vue 3 + Inertia.js module-based system.

Assumptions:

- `nwidart/laravel-modules` (or equivalent module package) is used.
- `Modules/Core` is mandatory and is the only shared module.
- `jooservices/dto` and `jooservices/client` are mandatory packages.

## How To Use

1. Read `00-project-structure.md` before creating files.
2. Apply `01` through `10`, `16` (contracts), and `17` (command/queue) while implementing features; use `15` for Jira-driven work (AI workflow and Owner approval gates).
3. Register deviations in `11-exceptions-registry.md` before implementation.
4. Use `09-feature-definition-of-done.md` as the completion gate.

## Glossary

- Module: Isolated feature boundary with BE + FE assets.
- Core Module: Shared contracts/utilities/base FE layer.
- Boundary Contract: Public interface/DTO schema crossing module/system boundaries.
- Exception: Time-boxed, approved deviation from baseline.

## Rule Locations

- Structure and module skeleton: `00`
- Dependency boundaries: `01`
- Routing/controller conventions: `02`
- Backend architecture: `03`
- Frontend architecture: `04`
- API contracts: `05`
- Data/storage standards: `06`
- Testing constitution: `07`
- Quality toolchain: `08`
- DoD and completion policy: `09`
- DTO/client policy: `10`
- Exception governance and refactor approvals: `11`
- Git hooks and quality gates: `12`
- Coverage policy: `13`
- Branch/commit/PR workflow: `14`
- Jira AI workflow and approval gates: `15`
- Contracts and interfaces (module-wide): `16`
- Command and queue service (Core): `17`

## Rule Format

Every policy uses:

- Rule
- Rationale
- Allowed
- Forbidden
- Verification

## Exception Workflow

Rule:
Any intentional deviation from any rule in this folder MUST be recorded in `11-exceptions-registry.md` using the official template before implementation.

Rationale:
Unregistered deviations become silent architecture drift.

Allowed:

```md
Exception ID: EX-2026-001
Rule Ref: 03-BE-004
Owner: team-auth
Expiry: 2026-06-30
```

Forbidden:

```md
# temporary hack, fix later
```

Verification:

- PR includes exception ID when rule is bypassed.
- Exception table contains owner, scope, reason, expiry, and rollback plan.

## Baseline Version Policy

Rule:
Pin service dependencies by `major.minor`, allow patch updates automatically.

Rationale:
Predictable runtime behavior while still getting security and bug patches.

Allowed:

```env
MARIADB_VERSION=11.8
MONGODB_VERSION=8.0
ELASTICSEARCH_VERSION=9.1
```

Forbidden:

```env
MARIADB_VERSION=latest
MONGODB_VERSION=8
```

Verification:

- Infra/env files avoid `latest` tags.
- Upgrade notes recorded when major/minor changes.
