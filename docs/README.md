# XCrawler Documentation

This folder contains the canonical documentation for the XCrawler repository. Documentation is split into five categories: **architecture** (the law), **reference** (the encyclopedia), **presentations** (the deck), **adr** (architecture decisions), and **playbooks** (operational checklists).

---

## Table of Contents

| Section                         | Path                  | Purpose                                          |
| ------------------------------- | --------------------- | ------------------------------------------------ |
| [Architecture](#architecture)   | `docs/architecture/`  | Enforceable rules, policies, and standards       |
| [Reference](#reference)         | `docs/reference/`     | Descriptive “how it works now” explanations      |
| [Presentations](#presentations) | `docs/presentations/` | Slide-style summaries for onboarding and reviews |
| [ADRs](#adrs)                   | `docs/adr/`           | Short, durable architecture decision records     |
| [Playbooks](#playbooks)         | `docs/playbooks/`     | Operational checklists and runbooks              |

---

## Which doc do I read?

- **I need to know what is allowed or forbidden**  
  → Read [docs/architecture/](architecture/). Start with [00-docs-classification](architecture/00-docs-classification.md), then the doc that matches your work (module boundaries, backend layering, data models, frontend, testing, code review).

- **I need to understand how the system works**  
  → Read [docs/reference/](reference/). Start with [00-system-overview](reference/00-system-overview.md), then [01-request-lifecycle](reference/01-request-lifecycle.md) and [02-module-map](reference/02-module-map.md).

- **I need to onboard someone or present the architecture**  
  → Use [docs/presentations/architecture-deck](presentations/architecture-deck.md).

- **I need to know why a structural decision was made**  
  → Browse [docs/adr/](adr/).

- **I need a step-by-step to implement a change without breaking rules**  
  → Use [docs/playbooks/00-pr-onboarding-playbook](playbooks/00-pr-onboarding-playbook.md).

---

## Architecture

**Location:** `docs/architecture/`

**Role:** Enforceable rules, policies, and standards. These documents define what the codebase must and must not do. They are the authority for code review and CI alignment.

| Document                                                             | Content                                                                                        |
| -------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------- |
| [00-docs-classification](architecture/00-docs-classification.md)     | What belongs in architecture vs reference vs presentations vs adr vs playbooks; move rules     |
| [01-module-boundaries](architecture/01-module-boundaries.md)         | Module dependency rules (MOD-001, MOD-002, …); allowed/forbidden diagrams                      |
| [02-backend-layering](architecture/02-backend-layering.md)           | Request lifecycle; Controller → FormRequest → Service → Repository → Model; forbidden patterns |
| [03-data-model-standards](architecture/03-data-model-standards.md)   | Model naming, TABLE/COLLECTION, fillable, timestamps; MySQL vs Mongo examples                  |
| [04-frontend-standards](architecture/04-frontend-standards.md)       | Page vs Component vs Composable vs Service; shared assets only in Core                         |
| [05-testing-standards](architecture/05-testing-standards.md)         | Feature vs unit tests; factories; Core TestCase; coverage and .env.testing                     |
| [06-code-review-checklist](architecture/06-code-review-checklist.md) | Checklist mapped to Rule IDs; blocker vs non-blocker severity                                  |

---

## Reference

**Location:** `docs/reference/`

**Role:** Descriptive “how it works now.” No new rules are introduced here; these docs explain the system and point to architecture docs as the rules authority.

| Document                                                  | Content                                                                           |
| --------------------------------------------------------- | --------------------------------------------------------------------------------- |
| [00-system-overview](reference/00-system-overview.md)     | Roles of MariaDB, Mongo, Redis, Elasticsearch; project purpose (crawling, search) |
| [01-request-lifecycle](reference/01-request-lifecycle.md) | Request flow with sequence diagrams (backend and frontend)                        |
| [02-module-map](reference/02-module-map.md)               | Core vs feature modules; example tree; how to add a new module                    |
| [03-jav-fetch-parse-flow](reference/03-jav-fetch-parse-flow.md) | JAV fetch/parse: process flow, logic flow, diagrams, onejav/141jav/ffjav, extension options |
| [diagrams/](reference/diagrams/)                          | Mermaid sources: module-deps.mmd, request-flow.mmd, data-flows.mmd                |

---

## Presentations

**Location:** `docs/presentations/`

**Role:** Slide-style summaries for onboarding and architecture reviews. Not the source of truth for rules; they summarize and link to architecture and reference docs.

| Document                                                | Content                                                                                                       |
| ------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| [architecture-deck](presentations/architecture-deck.md) | 12-slide deck: vision, stack, modules, backend, data, frontend, testing, CI, violations, “where to read more” |

---

## ADRs

**Location:** `docs/adr/`

**Role:** Short, durable records of architecture decisions (e.g. why the docs structure exists, consequences, alternatives considered).

| Document                                          | Content                                                                            |
| ------------------------------------------------- | ---------------------------------------------------------------------------------- |
| [0001-docs-structure](adr/0001-docs-structure.md) | Why docs are split into architecture / reference / presentations / adr / playbooks |

---

## Playbooks

**Location:** `docs/playbooks/`

**Role:** Operational checklists and runbooks (e.g. PR onboarding, deployment). At least one starter playbook is provided.

| Document                                                            | Content                                                                                                         |
| ------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| [00-pr-onboarding-playbook](playbooks/00-pr-onboarding-playbook.md) | Step-by-step: implement a change without violating rules (before coding, while coding, before PR, after review) |
| [01-jav-module-copy-refactor](playbooks/01-jav-module-copy-refactor.md) | Copy JAV module from XCrawlerII to Cursor and refactor to follow docs policies (deps, layering, routes, tests) |

---

## Quick reference

- **Rules authority:** `docs/architecture/`
- **Explanations:** `docs/reference/`
- **Slides / onboarding:** `docs/presentations/`
- **Decisions log:** `docs/adr/`
- **Runbooks:** `docs/playbooks/`
