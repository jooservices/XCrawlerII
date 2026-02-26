# 14 - Branch, Commit, and PR Rules

## Long-Lived Branches
Rule `14-GIT-001`:
Only two long-lived branches are allowed: `develop` (integration) and `master` (production).

Rationale:
Clear branch roles reduce release risk and workflow ambiguity.

Allowed:
```text
develop, master
```

Forbidden:
```text
release-main, prod2 as additional long-lived branches
```

Verification:
- Repository branch policy lists only `develop` and `master` as protected long-lived branches.

## Feature/Fix Branch Flow
Rule `14-GIT-002`:
`feature/*`, `fix/*`, and `chore/*` branches MUST be created from `develop` and merged via PR back to `develop`.

Rationale:
Keeps ongoing integration centralized and predictable.

Allowed:
```text
feature/auth-login from develop -> PR to develop
```

Forbidden:
```text
feature/auth-login from master -> PR to master
```

Verification:
- PR base branch for feature/fix/chore is `develop`.

## Hotfix Flow + Back-Merge
Rule `14-GIT-003`:
`hotfix/*` branches MUST be created from `master`, merged via PR to `master`, then back-merged via PR `master -> develop` immediately.

Rationale:
Prevents production fixes from being lost in integration branch.

Allowed:
```text
hotfix/payment-timeout from master -> PR to master -> PR master->develop
```

Forbidden:
```text
hotfix merged to master with no back-merge to develop
```

Verification:
- Every merged hotfix has a linked back-merge PR to `develop`.

## Conventional Commits
Rule `14-GIT-004`:
Commit messages MUST follow Conventional Commits format `type(scope): description`.

Rationale:
Consistent commit semantics improve auditability and release automation.

Allowed:
```text
feat(core): add action.auth.login endpoint
fix(auth): prevent duplicate session on retry
```

Forbidden:
```text
update
fix bug
wip
```

Verification:
- Commit lint check enforces Conventional Commits format.

## Pull Request Template
Rule `14-GIT-005`:
Every PR MUST include required template fields: scope, changes, tests run, coverage impact, risk, exceptions, and no-drive-by-refactor confirmation.

Rationale:
Structured PR metadata improves review quality and traceability.

Allowed:
```md
## Scope
## What changed
## Tests run
## Coverage impact
## Risk assessment
## Exceptions
## No drive-by refactor
```

Forbidden:
```md
PR with no tests and no risk section
```

Verification:
- PR template enforcement requires all mandatory sections before approval.

## Hotfix PR Additional Requirements
Rule `14-GIT-006`:
Hotfix PRs MUST include production impact, rollback plan, and linked `master -> develop` back-merge PR.

Rationale:
Production fixes require explicit operational safety and branch synchronization.

Allowed:
```md
Production impact: auth login outage
Rollback: revert commit <sha>
Back-merge PR: #123
```

Forbidden:
```md
hotfix PR merged without rollback/back-merge details
```

Verification:
- Hotfix PR checklist requires all three fields before merge.
