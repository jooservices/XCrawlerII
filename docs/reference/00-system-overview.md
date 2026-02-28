# 00 - System Overview

This document describes at a high level what the XCrawler system is, what technologies it uses, and how the main data stores fit in. It does **not** introduce new rules; the rules authority is [docs/architecture/](../architecture/).

---

## Project purpose

XCrawler is a modular Laravel/Vue platform for **crawling** (initially JAV-related sites) and **advanced search by metadata**. The main flow is:

1. **Discover** sources and targets.
2. **Fetch** pages and data from targets.
3. **Parse** and extract metadata.
4. **Normalize** metadata into stable contracts/schemas.
5. **Expose** fast structured search APIs (e.g. filters, range queries) over that metadata.

The architecture is designed so that additional content domains and target types can be added over time without rewriting the core platform.

---

## Data stores (roles)

| Store             | Role                                                                                                                                                                                                   |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **MariaDB**       | Relational, transactional data: users, auth, core app configuration, and any feature data that fits a relational model (e.g. jobs, runs). Primary SQL store.                                           |
| **MongoDB**       | Document store for semi-structured or high-volume data: logs (e.g. client logs, crawler logs), request/response payloads, and other data that benefits from a flexible schema or is append-heavy.      |
| **Redis**         | Caching, sessions, queues, rate limiting, and any short-lived or ephemeral state. Optional: idempotency keys, locks.                                                                                   |
| **Elasticsearch** | Search index over normalized metadata. Used for fast structured filtering, range queries (numeric/date), and combining exact filters with relevance. The “advanced search” capability is backed by ES. |

Data flow at a high level:

- **Crawl pipeline** produces raw and normalized metadata; normalized data is persisted (e.g. MariaDB/Mongo) and indexed in **Elasticsearch** for search APIs.
- **Logs and telemetry** typically go to **MongoDB** (and optionally to logging/monitoring pipelines).
- **User and app state** use **MariaDB** and **Redis** as appropriate.

---

## Tech stack (summary)

- **Backend:** Laravel 12, PHP 8.5.
- **Frontend:** Vue 3, Inertia.js, PrimeVue, FontAwesome, Vite; Composition API and `<script setup lang="ts">` only.
- **Modules:** `nwidart/laravel-modules`; `Modules/Core` is the only shared module; feature modules depend only on Core.
- **Boundary packages:** `jooservices/dto`, `jooservices/client` (and others as per composer).

---

## Rules authority

All enforceable rules (module boundaries, backend layering, data model standards, frontend standards, testing, code review) live in **docs/architecture/**. This overview is descriptive only. When in doubt, follow:

- [docs/architecture/01-module-boundaries.md](../architecture/01-module-boundaries.md)
- [docs/architecture/02-backend-layering.md](../architecture/02-backend-layering.md)
- [docs/architecture/03-data-model-standards.md](../architecture/03-data-model-standards.md)
- [docs/architecture/04-frontend-standards.md](../architecture/04-frontend-standards.md)
- [docs/architecture/05-testing-standards.md](../architecture/05-testing-standards.md)
- [docs/architecture/06-code-review-checklist.md](../architecture/06-code-review-checklist.md)

---

## References

- [01-request-lifecycle](01-request-lifecycle.md) — Request flow and sequence
- [02-module-map](02-module-map.md) — Core vs feature modules and how to add a module
- [diagrams/](diagrams/) — Mermaid diagrams (module deps, request flow, data flows)
