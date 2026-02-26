# AI Operating Policy

## Scope
This policy defines mandatory operating rules for all AI-assisted work in this repository.

## Authority Order
1. Project architecture and testing rules under `docs/architecture/*`
2. This AI operating policy
3. Task-specific prompt template under `docs/skills/prompts/*`

If rules conflict, architecture docs win.

## Mandatory Rules

### AIOP-001 No Guessing
Rule:
If required data is missing, AI MUST mark `UNKNOWN` and list explicit assumptions.

Verification:
- Output contains `UNKNOWN` block when inputs are incomplete.

### AIOP-002 Scoped Changes Only
Rule:
AI MUST enforce scoped changes as defined canonically in `09-DOD-003` (`docs/architecture/09-feature-definition-of-done.md`).

Verification:
- Changed file list maps to scope.
- Out-of-scope changes require approved Refactor Request.

### AIOP-003 Reuse-First Gate
Rule:
Canonical reuse-first policy is `00-STR-003` (`docs/architecture/00-project-structure.md`). AI MUST enforce reuse analysis before new class/file creation using its allowed criteria.

Verification:
- Output includes reuse analysis and selected criterion for each new class/file.

### AIOP-004 Zero Hardcode
Rule:
AI MUST enforce canonical zero-hardcode policies in `03-BE-009` and `04-FE-007`.

Verification:
- Changed logic files contain enum/const usage for domain values.

### AIOP-005 DTO Anti-Abuse
Rule:
DTOs are boundary-only (HTTP/event/integration/cross-module contracts). Internal method-to-method DTO passing is forbidden. Feature DTO budget is 2-4 by default and counts all DTOs introduced by the feature, including Core-placed DTOs.

Verification:
- Output contains DTO count and boundary justification per DTO.

### AIOP-006 Pint-First Formatting
Rule:
Pint is formatting authority. AI MUST not fight Pint with conflicting manual style edits.

Verification:
- Tool order in report: Pint -> PHPCS -> PHPStan/Larastan -> PHPMD.

### AIOP-007 Backend Test Framework
Rule:
Backend tests MUST use PHPUnit only. Pest syntax/runner is forbidden.

Verification:
- No `pestphp/pest` dependency.
- No backend test files starting with `it(` or `test(`.

### AIOP-008 FE Test Tooling
Rule:
FE unit/component tests MUST use Vitest + Vue Test Utils. FE E2E MUST use Playwright.

Verification:
- FE test files and dependencies align with required tools.

### AIOP-009 Comment Policy
Rule:
Comments are WHY-only. WHAT-comments are forbidden.

Verification:
- New comments explain rationale/tradeoff/invariant, not obvious operations.

### AIOP-010 DI Policy
Rule:
Constructor injection must stay lean. Rare dependencies use method injection/controlled resolve. >5 constructor dependencies requires justification.

Verification:
- Constructor dependency count reviewed for changed classes.

## Stop Conditions (Hard Gate)

### AIOP-011 Broad Refactor Stop
If requested work requires broad refactor outside approved scope, AI MUST stop and output a Refactor Request using project template. Wait for approval before coding.

### AIOP-012 Breaking API Contract Stop
If requested work breaks existing API contract, AI MUST stop and propose versioning/migration plan (`/api/v{n}` strategy + compatibility notes). Wait for approval.

## Required Output Contract (All AI Runs)
- Changed file list
- Scope statement
- Assumptions/UNKNOWN list (if any)
- Unified diffs or patch-set only (no full-file dump unless explicitly requested)
- Verification commands and expected outcomes

## References
- GPT-5.3-Codex model capabilities: https://developers.openai.com/api/docs/models/gpt-5.3-codex
- Claude prompting best practices: https://docs.anthropic.com/en/docs/build-with-claude/prompt-engineering/claude-4-best-practices
- Gemini models and long context: https://ai.google.dev/models/gemini and https://ai.google.dev/gemini-api/docs/long-context
- Laravel Pint (opinionated, default in new Laravel apps): https://laravel.com/docs/12.x/pint
