# AI Usage Matrix

## Model-to-Job Mapping
| Job Type | Primary Model | Secondary Model | Why This Model | Output Contract | Verification |
|---|---|---|---|---|---|
| Code generation / large diffs / patch application | GPT-5.3-Codex | Claude Sonnet/Opus | Codex is optimized for agentic coding with large context and high output budget. | Diff-only or unified patch-set + changed file list + test commands. | Pint, PHPCS, PHPStan/Larastan, PHPMD, PHPUnit, Vitest, Playwright (as applicable). |
| PHPUnit test generation at scale | GPT-5.3-Codex | Claude Sonnet | Strong code-edit reliability and long-horizon refactors while preserving scope. | Test-file diffs only + test matrix mapping cases to assertions. | `phpunit` suites + no Pest syntax/dependency checks. |
| Adversarial review / debate / loophole hunting | Claude Opus/Sonnet | GPT-5.3-Codex | Claude performs strongly in critique and contradiction spotting. | Checklist-only findings + minimal patch-set proposals. | Re-run required tests/tools after patch application. |
| Full docs consistency audit (cross-file contradictions) | Claude Opus | Gemini 3.1 Pro | Claude is strong at policy contradiction analysis; Gemini helps long-context scan. | Contradiction report + minimal doc patch recommendations. | Rule-ID cross-reference check + grep-based policy presence checks. |
| Ultra-long-context ingestion (entire docs tree / repo indexing) | Gemini 3.1 Pro | Claude Opus | Gemini supports 1M-token context class workloads for large corpus understanding. | Consistency report + missing-rule checklist + candidate minimal patches. | Apply patches in scope, then rerun matrix checks/tools. |
| Quick formatting/transforms (small tasks) | GPT-5.3-Codex | Claude Sonnet | Fast and reliable for short deterministic edits. | Minimal diff-only output; no unrelated file edits. | Targeted command checks only (lint/test subset). |

## Global Constraints (applies to every row)
- Scoped changes only.
- Reuse-first and DTO anti-abuse gates.
- Pint-first style authority.
- PHPUnit-only backend tests.
- FE tests use Vitest + Vue Test Utils + Playwright.
- Canonical prompt templates are under `docs/skills/prompts/` (single source).

## Source Notes
- GPT-5.3-Codex capabilities: https://developers.openai.com/api/docs/models/gpt-5.3-codex
- Claude prompting guidance: https://docs.anthropic.com/en/docs/build-with-claude/prompt-engineering/claude-4-best-practices
- Gemini 3 and long context: https://ai.google.dev/models/gemini and https://ai.google.dev/gemini-api/docs/long-context
- Pint defaults and philosophy: https://laravel.com/docs/12.x/pint
