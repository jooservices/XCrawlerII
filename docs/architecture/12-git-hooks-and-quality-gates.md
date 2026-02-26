# 12 - Git Hooks and Quality Gates

## Pre-Commit Hook Gate
Rule `12-GAT-001`:
A pre-commit hook MUST run Pint in test mode and block commits on any formatting issue.

Rationale:
Fast local enforcement prevents formatting drift from entering history.

Allowed:
```bash
./vendor/bin/pint --test
```

Forbidden:
```bash
# commit without running formatter gate
```

Verification:
- Hook script includes `pint --test`.
- Commit is rejected when Pint reports violations.

## Full Quality Gate
Rule `12-GAT-002`:
A full gate MUST run Pint, PHPCS, PHPStan/Larastan, PHPMD, PHPUnit, coverage generation (`--coverage-clover`) with threshold check >=90% for changed scope, and FE test stack (Vitest + Vue Test Utils, Playwright where applicable). This full gate MUST be enforced as a required CI check on PR merge and/or pre-push hook.

Rationale:
Combined static + dynamic checks are required for production-readiness.

Allowed:
```text
pre-commit: pint --test
full gate (CI required): pint --test -> phpcs -> phpstan -> phpmd -> phpunit --coverage-clover -> coverage-threshold-check(>=90%) -> vitest -> playwright
```

Forbidden:
```text
run phpunit only and skip static analysis
```

Verification:
- Gate script/pipeline definition contains all required commands.
- Toolchain order remains Pint-first for formatting conflicts.
- PR merge is blocked when required full gate check fails.
- Coverage threshold check references `13-coverage-policy.md`.

## Zero-Issue Policy
Rule `12-GAT-003`:
Project code quality gate is zero-issue: no lint/type/test failures are allowed in merged changes.

Rationale:
Allowing known failures normalizes debt and degrades release confidence.

Allowed:
```text
All gate checks green before merge.
```

Forbidden:
```text
Merge with known PHPStan errors to fix later.
```

Verification:
- Merge approval requires successful gate evidence.
- Any temporary bypass requires Exception Registry entry.

## Scope Enforcement
Rule `12-GAT-004`:
Quality fixes MUST remain within approved feature scope; broad cleanup/refactor requires approved Refactor Request.

Rationale:
Quality enforcement cannot be used to smuggle unrelated changes.

Allowed:
```text
Fix only violations in modified module files.
```

Forbidden:
```text
Refactor unrelated modules under "lint cleanup".
```

Verification:
- Diff scope review matches approved feature scope.
- Out-of-scope fixes include approved RR ID.

## Commit Message Lint Gate
Rule `12-GAT-005`:
`commit-msg` hook MUST enforce Conventional Commits format (`type(scope): description`), using a commit lint tool (e.g., commitlint) or equivalent checker.

Rationale:
Consistent commit semantics are required for traceability and release hygiene.

Allowed:
```text
feat(auth): add action.auth.login endpoint
fix(core): block duplicate idempotency key replay
```

Forbidden:
```text
update
fix bug
wip
```

Verification:
- `commit-msg` hook validates Conventional Commits.
- Invalid commit messages are rejected locally or in CI.

## PHPUnit-Only/Pest Detection Gate
Rule `12-GAT-006`:
CI MUST fail if `composer.json` contains `pestphp/pest` OR Pest syntax exists in backend tests (`it(` or `test(` at line start under `Modules/*/tests`).

Rationale:
Backend testing policy is PHPUnit-only and must be automatically enforced.

Allowed:
```bash
rg -n '^\\s*(it|test)\\(' Modules/*/tests && exit 1 || true
rg -n 'pestphp/pest' composer.json && exit 1 || true
```

Forbidden:
```text
manual policy check only, no automated Pest detection
```

Verification:
- CI includes both dependency and syntax checks.
- Failing checks block merge.

## DTO Budget Reviewer Gate
Rule `12-GAT-007`:
When no automated DTO counter exists, reviewer gate MUST manually verify DTO budget compliance (2-4 per feature, including Core-placed DTOs) or approved exception linkage.

Rationale:
DTO anti-abuse policy requires explicit enforcement even without automation.

Allowed:
```text
PR checklist includes: DTO count = 3 (incl. 1 Core DTO), within budget.
```

Forbidden:
```text
DTO count not reviewed because no automation exists.
```

Verification:
- PR template/review checklist includes DTO count and exception ID when needed.
