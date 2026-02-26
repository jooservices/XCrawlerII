# AI Runbooks

Prompt template path policy:
- Canonical prompt templates live under `docs/skills/prompts/`.

## Runbook 1: Implement Feature
1. Define scope and acceptance criteria from feature spec.
2. GPT-5.3-Codex implements code + tests (diff-only).
3. Claude audits output for loopholes/contradictions and returns patch-set only.
4. GPT-5.3-Codex applies approved minimal patches.
5. Optional Gemini 3.1 Pro long-context sweep for cross-file consistency.
6. Run verification gates:
   - Pint -> PHPCS -> PHPStan/Larastan -> PHPMD
   - PHPUnit (backend)
   - Vitest + Vue Test Utils + Playwright (frontend as applicable)
7. Produce evidence bundle: changed files, diffs, tool/test outcomes, exceptions if any.

Stop conditions:
- Broad refactor required -> stop and issue Refactor Request.
- Breaking API contract required -> stop and propose versioning plan.

## Runbook 2: Docs Update
1. GPT-5.3-Codex drafts minimal doc diffs in target files only.
2. Claude reviews for policy drift, duplication, and inconsistent rule IDs.
3. Optional Gemini 3.1 Pro scans full docs tree for contradictions.
4. Apply minimal patch-set and rerun doc checks.

Output contract:
- Changed files
- Section-level diffs only
- Cross-reference validation notes

## Runbook 3: Hotfix (Minimal Diff)
1. Lock exact failure scope and reproduction.
2. GPT-5.3-Codex produces smallest possible fix + focused tests.
3. Claude performs quick adversarial audit.
4. Run minimal required gates for impacted surface.
5. Publish patch with rollback note.

Constraints:
- No unrelated refactor.
- No schema/contract expansion unless approved.
- Keep patch minimal and reversible.
