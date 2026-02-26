# AI Operational Rules

## No Guessing
Rule `AIR-001`:
If required input is missing or ambiguous, AI must request clarification or mark explicit assumptions.

Rationale:
Guessing creates incorrect architecture and hidden rework.

Allowed:
```md
Assumption A1: module package is nwidart/laravel-modules.
```

Forbidden:
```md
Implemented based on likely behavior without noting assumption.
```

Verification:
- Output contains `Assumptions` section when needed.

## Reuse-First
Rule `AIR-002`:
AI must reuse/update existing classes before creating new ones.

Rationale:
Avoids unnecessary class proliferation.

Allowed:
```md
Updated existing AuthService to add token rotation.
```

Forbidden:
```md
Created AuthServiceV2 without reuse analysis.
```

Verification:
- New classes include explicit reuse analysis + criterion.

## Zero Hardcode
Rule `AIR-003`:
AI must not introduce raw domain literals in BE/FE logic, validation, tests, or mappings.

Rationale:
Hardcoded values drift and break consistency.

Allowed:
```php
UserRole::ADMIN
```

Forbidden:
```php
'admin'
```

Verification:
- Scan changed files for domain literals.

## Comment WHY-only
Rule `AIR-004`:
AI MUST enforce the canonical comment policy in `03-BE-010` (`docs/architecture/03-backend-architecture-rules.md`): WHY-only comments, no WHAT-comments.

Rationale:
Avoid duplicate policy sources while keeping AI behavior enforceable.

Allowed:
```php
Apply `03-BE-010` in all generated/edited backend code comments.
```

Forbidden:
```php
Ignore `03-BE-010` and add WHAT-comments.
```

Verification:
- AI output references `03-BE-010` when comment-policy checks are reported.

## DI Policy
Rule `AIR-005`:
AI MUST enforce the canonical DI policy in `03-BE-011` (`docs/architecture/03-backend-architecture-rules.md`): lean constructor, rare deps via method injection/controlled resolve, >5 deps justification required.

Rationale:
Avoid duplicate policy sources while keeping AI behavior enforceable.

Allowed:
```php
Apply `03-BE-011` and include >5-dependency justification when applicable.
```

Forbidden:
```php
Ignore `03-BE-011` and add overloaded constructors without justification.
```

Verification:
- AI output references `03-BE-011` for DI checks.

## Early Returns
Rule `AIR-006`:
AI MUST enforce the canonical early-return policy in `03-BE-012` (`docs/architecture/03-backend-architecture-rules.md`): prefer guard clauses and reduce nesting depth.

Rationale:
Avoid duplicate policy sources while keeping AI behavior enforceable.

Allowed:
```php
Apply `03-BE-012` in complex control-flow methods.
```

Forbidden:
```php
Ignore `03-BE-012` and keep deep nested condition pyramids.
```

Verification:
- AI output references `03-BE-012` for control-flow checks.

## No Drive-by Refactor
Rule `AIR-007`:
AI must not refactor unrelated code while delivering a scoped feature without approved Refactor Request.

Rationale:
Prevents hidden risk and diff inflation.

Allowed:
```md
Unrelated cleanup deferred; RR required.
```

Forbidden:
```md
Refactored unrelated module during feature implementation.
```

Verification:
- Diff scope matches feature scope or approved RR exists.

## Feature-Scoped Code and Tests
Rule `AIR-008`:
AI may only modify code/tests directly relevant to feature scope.

Rationale:
Keeps review focused and regression surface controlled.

Allowed:
```md
Changed Auth module code + Auth tests only.
```

Forbidden:
```md
Touched Billing tests while implementing Auth feature.
```

Verification:
- Changed files map to approved scope.

## DTO Anti-Abuse Enforcement Checklist
Rule `AIR-009`:
Before introducing a DTO, AI must check:
1. Is this boundary I/O?
2. Does DTO add normalization/invariant/mapping/stable contract value?
3. Is DTO count within budget (2-4 per feature)?
4. Can typed params/value object replace it?

Rationale:
Stops DTO spam and preserves clarity.

Allowed:
```md
Created 2 boundary DTOs for API request/response.
```

Forbidden:
```md
Created DTOs for every internal service call.
```

Verification:
- PR/spec includes DTO checklist results.

## Skill Execution Rules (Mandatory)
Rule `AIR-010`:
Skill runs MUST apply reuse-first: no new class/file unless at least one criterion is met (SRP, >=2 call-sites, maintainability threshold, adapter seam).

Rationale:
Prevents class/file explosion and keeps scope controlled.

Verification:
- Every created file has explicit justification.

Rule `AIR-011`:
Skill runs MUST keep feature-scoped changes only; broader refactor requires approval.

Rule `AIR-012`:
Skill runs MUST enforce DTO anti-abuse and budget counting.

Rule `AIR-013`:
Quality fixes MUST follow Pint-first order: Pint -> PHPCS -> PHPStan/Larastan -> PHPMD.

Rule `AIR-014`:
Tests generated/modified by skills MUST be meaningful; placeholder tests are forbidden.

Rule `AIR-015`:
Comments added by skills MUST be WHY-only.

Rule `AIR-016`:
Default output mode for implementation and audit tasks is diff-only/patch-only unless explicitly asked for full-file regeneration.

Rationale:
Diff-first output keeps reviews deterministic and minimizes accidental drift.

Verification:
- Output contains changed file list and patches only by default.

Rule `AIR-017`:
No markdown littering: do not create ad-hoc temporary `.md` files outside approved doc paths (`docs/architecture`, `docs/skills`) unless explicitly requested.

Rationale:
Untracked markdown clutter reduces discoverability and causes stale guidance risk.

Verification:
- New markdown files are either requested or inside approved documentation paths.
