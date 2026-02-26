# 09 - Feature Definition of Done

## Feature Spec Template
```md
# Feature Spec: <feature-name>

## Objective
<single measurable objective>

## Acceptance Criteria
1. <criterion 1>
2. <criterion 2>
3. <criterion 3>

## Scope
- In scope: <modules/files>
- Out of scope: <explicit exclusions>

## Contracts
- Routes: <render/action/api.v1 route names>
- Request/Response DTOs: <boundary DTO list>
- Error codes: <enum/constants>

## Data
- Migration changes: <yes/no + files>
- Factory states: <list>

## Observability
- Logs: <event names + fields>
- Metrics: <counter/timer names>
- Trace IDs: <propagation path>

## New Class Justification
- <class>: <why required + criterion>

## Exception IDs
- EX-YYYY-NNN (if any)
```

## No Done Khong Rule
Rule `09-DOD-001`:
Feature cannot be marked done unless all objective checklist items pass.

Rationale:
Prevents done claims without engineering proof.

Allowed:
```md
DoD status: PASS (all items checked, evidence linked)
```

Forbidden:
```md
DoD status: done because code compiles
```

Verification:
- Checklist completed with references to tests/tool outputs/docs updates.

## Objective DoD Checklist
Rule `09-DOD-002`:
All items below are mandatory unless exception-registered.

Rationale:
Standardized quality gate across teams and AI contributors.

Allowed:
```md
- [x] Spec exists with acceptance criteria
- [x] Routes grouped and named correctly
- [x] Thin controller confirmed
- [x] Service/repository/model layering confirmed
- [x] Zero hardcode (enum/const)
- [x] DB migration + factory states (if needed)
- [x] Tests pass (BE/FE as applicable)
- [x] Coverage report attached (>=90% changed scope; see 13-coverage-policy.md)
- [x] DTO count within budget (2-4) or exception-registered
- [x] Pint/PHPStan/PHPMD/PHPCS pass
- [x] Observability hooks for mutations
- [x] Docs updated
```

Forbidden:
```md
- [x] feature works on my machine
```

Verification:
- PR template includes exact checklist above.

## Scoped Change Policy
Rule `09-DOD-003`:
Only modify code and tests within requested feature scope. Unrelated refactors require approved Refactor Request.

Rationale:
Minimizes regression surface and review ambiguity.

Allowed:
```md
Touched: Modules/Auth/* and related Auth tests only.
```

Forbidden:
```md
Also refactored unrelated Billing repositories.
```

Verification:
- Diff review confirms scope boundaries.

## Observability Requirement
Rule `09-DOD-004`:
State-changing operations MUST include structured logs and traceable error context.

Rationale:
Production diagnosis requires deterministic telemetry.

Allowed:
```php
$this->logger->info('auth.login.success', ['user_id' => $userId, 'trace_id' => $traceId]);
```

Forbidden:
```php
logger('done');
```

Verification:
- Mutation services emit structured event names and required context fields.
