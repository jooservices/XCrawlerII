# Prompt Template: FE Component Test Generator

## Required Inputs
- Component paths
- Behavior requirements

## Expected Outputs
- `tests/Frontend/unit/*.spec.ts`

## Constraints
- Vitest + Vue Test Utils only
- Behavior assertions required
- No placeholder/snapshot-only tests

## DoD
1. Render/interactions/events covered.
2. Edge/error states covered.

## Stop and Request Approval
- Stop if testability requires out-of-scope refactor.
