# 00 - Documentation Classification

## Purpose

Define what belongs in **architecture**, **reference**, **presentations**, **adr**, and **playbooks**, and how to refactor docs as the repo grows.

## Scope

- All documentation under `docs/`
- Contributors and maintainers who create or move documentation

## Non-goals

- Content of individual architecture rules (see 01–06)
- Tool-specific writing style (e.g. JSDoc, PHPDoc)

## Definitions

| Term              | Meaning                                                                       |
| ----------------- | ----------------------------------------------------------------------------- |
| **Architecture**  | Enforceable rules, policies, and standards. The “law” for the codebase.       |
| **Reference**     | Descriptive “how it works now.” The “encyclopedia.” No new rules.             |
| **Presentations** | Slide-style summaries for onboarding and reviews. The “deck.”                 |
| **ADR**           | Architecture Decision Record: short, durable record of a structural decision. |
| **Playbook**      | Operational checklist or runbook (e.g. PR onboarding, deployment).            |

---

## Classification Rules

### What belongs in `docs/architecture/`

- **Rule IDs** (e.g. MOD-001, BE-REQ-001, DATA-MOD-001, FE-ARCH-001, TEST-001).
- **Allowed vs forbidden** patterns with examples and anti-examples.
- **Enforcement** (PR/CI) and references to other internal docs.
- **Non-goals** and **definitions** when needed.

**Do not put here:** Long narrative explanations of “how the system works” (those go in reference). Slide content (that goes in presentations).

### What belongs in `docs/reference/`

- **Descriptive** overviews (system overview, request lifecycle, module map).
- **Diagrams** (Mermaid) that explain flow or structure.
- **“How to add X”** steps that describe current process (not new policy).
- **Links back to architecture** as the rules authority.

**Do not put here:** New rules or policy. If you find yourself writing “must” or “shall” for the first time, consider moving that to architecture.

### What belongs in `docs/presentations/`

- **Slide-style** content (title lines like `# Slide: …`, short bullet lists).
- **Summaries** of architecture and reference for onboarding or reviews.
- **Links** to architecture and reference for “read more.”

**Do not put here:** The canonical definition of a rule. The deck points to architecture; it does not replace it.

### What belongs in `docs/adr/`

- **Numbered ADRs** (e.g. 0001-docs-structure.md).
- **Context, decision, consequences, alternatives considered.**
- Short and durable; no lengthy tutorials.

**Do not put here:** New feature specs or runbooks (use playbooks or product docs).

### What belongs in `docs/playbooks/`

- **Step-by-step** operational procedures (e.g. “before coding”, “before PR”, “after review”).
- **Checklists** that reduce human error.
- Optional: deployment, incident, or release runbooks.

**Do not put here:** New architecture rules (those go in architecture).

---

## Move Rules (refactoring docs as the repo grows)

1. **New rule or policy**  
   Add to the appropriate `docs/architecture/` file (or create a new one with next number). Do not add new rules in reference or presentations.

2. **Explaining existing behavior**  
   Add or update `docs/reference/`. If the explanation contradicts a rule, fix the rule in architecture first, then align reference.

3. **Slide or onboarding summary**  
   Add or update `docs/presentations/`. Keep bullets short; link to architecture and reference for detail.

4. **Structural decision**  
   Add a new ADR in `docs/adr/` with next number (e.g. 0002-…). One decision per file.

5. **Operational procedure**  
   Add or update a playbook in `docs/playbooks/`. Name consistently (e.g. `00-pr-onboarding-playbook.md`).

6. **When in doubt**  
   Prefer putting “must/shall” in architecture and “how it works” in reference. Move content later if the boundary was wrong.

---

## Enforcement

- **PR:** Reviewers check that new docs are in the right folder and that reference/presentations do not introduce new rules.
- **CI:** Optional: link checker or doc lint; not required for this classification doc alone.
- **References:** Each architecture doc should list “References” (internal docs). Reference docs should link to architecture as “rules authority.”

---

## References

- [docs/README.md](../README.md) — Documentation index and “which doc do I read?”
- [ADR 0001 — Docs structure](../adr/0001-docs-structure.md) — Why this split exists
