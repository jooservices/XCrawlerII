# 13 - Coverage Policy

## Coverage Threshold
Rule `13-COV-001`:
Each feature/module change MUST maintain at least 90% automated test coverage; target is 100% class coverage for changed scope.

Rationale:
High coverage reduces regression risk in modular architecture.

Allowed:
```text
Module coverage: 93% (target tracked toward 100%).
```

Forbidden:
```text
Module coverage: 71% with no exception.
```

Verification:
- Coverage report attached to PR for changed module/feature scope.
- Coverage below 90% requires approved exception.

## FormRequest Coverage
Rule `13-COV-002`:
FormRequest classes in changed scope MUST have direct tests.

Rationale:
Validation and authorization regressions are high-impact and easy to miss.

Allowed:
```text
tests/FormRequest/Auth/LoginRequestTest.php present for LoginRequest.
```

Forbidden:
```text
New FormRequest added without tests.
```

Verification:
- FormRequest file list matches FormRequest test file list.

## Coverage Measurement Scope
Rule `13-COV-003`:
Coverage is measured per changed module/feature scope, not repository-wide average only.

Rationale:
Global averages can hide low-quality changes in a single module.

Allowed:
```text
Coverage report segmented by Modules/Auth for Auth feature PR.
```

Forbidden:
```text
Only global coverage shown, module-level drop hidden.
```

Verification:
- Coverage output identifies changed module/class scope.

## Test Type Expectations
Rule `13-COV-004`:
Coverage must come from meaningful tests across mandatory categories: happy, unhappy, weird, security/exploit, edges.

Rationale:
Line coverage alone is insufficient without scenario quality.

Allowed:
```text
Coverage includes exploit and edge-case assertions.
```

Forbidden:
```text
Coverage achieved via placeholder tests.
```

Verification:
- Test matrix maps acceptance criteria and required categories.
- Placeholder assertions are absent.

## FE Coverage Expectation
Rule `13-COV-005`:
Frontend changed scope MUST include unit/component coverage (Vitest + Vue Test Utils) and Playwright E2E for critical user flows.

Rationale:
Critical UX regressions require both fast component checks and full-flow validation.

Allowed:
```text
Unit specs + critical login checkout E2E included.
```

Forbidden:
```text
Only unit tests, no E2E for critical flow.
```

Verification:
- FE test report includes Vitest and Playwright outputs for changed critical flows.
