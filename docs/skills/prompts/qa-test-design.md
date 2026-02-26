# Prompt Template: QA Test Design

## Input
- Feature spec
- Risk analysis

## Required Output
- BE test matrix (Feature/Unit/FormRequest)
- FE test matrix (unit/E2E)
- Traceability map

## Constraints
- Must include happy/unhappy/weird/security/edge
- No placeholder tests
- Backend PHPUnit only
- FE uses Vitest + Vue Test Utils + Playwright

## DoD
1. Every acceptance criterion mapped to tests.
2. Required categories complete.
3. Correct test location/classification.
