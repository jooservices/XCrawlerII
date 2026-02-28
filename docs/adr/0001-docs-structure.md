# ADR 0001: Documentation structure (architecture / reference / presentations / adr / playbooks)

## Status

Accepted.

## Context

The project needed a clear, maintainable documentation layout that:

- Separates **enforceable rules** from **descriptive explanations** so that “the law” is easy to find and does not get diluted in long narratives.
- Gives new contributors and reviewers a single place to understand “which doc do I read?” and how to refactor docs as the repo grows.
- Supports **onboarding and reviews** with slide-style material without duplicating the canonical rules.
- Records **architecture decisions** in short, durable form (ADRs).
- Provides **operational procedures** (playbooks) for common workflows (e.g. PR onboarding).

## Decision

We adopt a five-way split under `docs/`:

1. **docs/architecture/** — The “law.” Enforceable rules, policies, and standards. Rule IDs (MOD-_, BE-REQ-_, DATA-MOD-_, FE-ARCH-_, TEST-\*). No long narrative; allowed/forbidden, examples, anti-examples, enforcement, references.
2. **docs/reference/** — The “encyclopedia.” Descriptive “how it works now”: system overview, request lifecycle, module map. No new rules; links to architecture as rules authority. Diagrams (Mermaid) live under `docs/reference/diagrams/`.
3. **docs/presentations/** — The “deck.” Slide-style summaries for onboarding and architecture reviews. Max ~8 bullets per slide; link to architecture and reference for detail. Not the source of truth for rules.
4. **docs/adr/** — Architecture Decision Records. Numbered (0001, 0002, …). Short: context, decision, consequences, alternatives. One decision per file.
5. **docs/playbooks/** — Operational checklists and runbooks (e.g. “how to implement a change without violating rules”). Step-by-step; before/while/after sections.

A single **docs/README.md** provides a table of contents and “Which doc do I read?” routing so that architecture = rules, reference = explanations, presentations = slide decks.

## Consequences

- **Positive:** Reviewers and contributors know where to find rules (architecture) vs explanations (reference). Refactoring docs is guided by “move rules” in 00-docs-classification. Onboarding can use the deck without reading every rule doc. ADRs give a durable record of structural decisions.
- **Negative:** Some overlap is possible (e.g. a diagram in reference and a summary in the deck); we accept that and keep the deck as a summary only. New contributors must be pointed to docs/README.md once.
- **Follow-up:** Keep architecture docs to 00–06 as the core set; add new numbered docs only when a new domain (e.g. security, deployment) needs its own rule set. New rules go into architecture; new “how it works” content goes into reference.

## Alternatives considered

- **Single “Architecture” folder with mixed rules and narrative:** Rejected because rules would be hard to grep and enforce; code review checklist would not map cleanly to sections.
- **Wiki or external docs only:** Rejected to keep docs versioned with the code and in the same repo.
- **No presentations folder:** Rejected; having a single deck reduces duplicate “intro” decks and keeps onboarding consistent.
- **No playbooks:** Rejected; at least one PR-onboarding playbook reduces repeated “how do I do this without breaking rules?” questions.

## References

- [docs/README.md](../README.md)
- [docs/architecture/00-docs-classification.md](../architecture/00-docs-classification.md)
