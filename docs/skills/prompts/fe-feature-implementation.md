# Prompt Template: FE Feature Implementation

## Input
- Feature spec path
- Module FE scope
- Page flow + API contract

## Required Output
- FE pages/components/composables/services/types
- Unit/component tests + critical E2E tests

## Constraints
- Page orchestration/layout only
- Components presentational
- Composables state/fetch logic
- Services are API/action clients only
- Use Core MasterLayout/base components
- Vitest + Vue Test Utils + Playwright

## DoD
1. Tests separated from FE code.
2. No domain literals in FE logic.
3. Critical flow E2E exists and passes.
