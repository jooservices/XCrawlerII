# Prompt Template: FE E2E Flow Generator

## Required Inputs
- Critical flows
- Env/auth setup

## Expected Outputs
- `tests/Frontend/e2e/*.spec.ts`
- Optional fixtures

## Constraints
- Playwright only
- Assert user-visible outcomes
- Stable selectors

## DoD
1. Critical flows pass in clean environment.
2. At least one mutation failure/retry path covered.

## Stop and Request Approval
- Stop if flow requires out-of-scope navigation refactor.
