# Analytics Documentation Index

This folder contains **dedicated documentation for the XCrawlerII analytics module**. It is written so that Business Analysts (BA), freshers, and developers can understand what analytics does, why it exists, how to use it, and how it is implemented.

---

## What is covered here

- **What / Why / How** the analytics system was built and what business value it delivers.
- **How to use** analytics from the frontend (FE), backend (BE), admin UI, and CLI.
- **Code structure** and class relationships (FE and BE).
- **Request lifecycle** from user action to stored metric and admin dashboard, with diagrams.
- **Data model** for Redis, MongoDB, and MySQL in the analytics context.

---

## Reading order

| Order | Document | Best for |
|-------|----------|----------|
| 1 | [Overview](overview.md) | BA, freshers, anyone new to analytics – business view, glossary, KPIs. |
| 2 | [Usage](usage.md) | FE/BE developers – how to track events and use admin/CLI. |
| 3 | [Code structure](code-structure.md) | Developers – where classes live and how they relate. |
| 4 | [Request lifecycle](request-lifecycle.md) | Developers and operators – step-by-step flow and diagrams. |
| 5 | [Data model](data-model.md) | Developers – Redis keys, Mongo collections, MySQL fields. |

---

## Quick links

- **API reference (ingest + admin endpoints):** [../api/api-reference.md](../api/api-reference.md)
- **Implementation guide (e.g. adding a new action):** [../guides/implementation-guide.md](../guides/implementation-guide.md)
- **Testing strategy (analytics tests):** [../testing/testing-strategy.md](../testing/testing-strategy.md)
- **Deployment (scheduler, env vars):** [../deployment/deployment-guide.md](../deployment/deployment-guide.md)
- **Troubleshooting / FAQ:** [../troubleshooting/faq.md](../troubleshooting/faq.md)
- **Architecture overview (whole project):** [../architecture/overview.md](../architecture/overview.md)

---

## Validation checklist (analytics docs)

- [x] A BA can explain what analytics does and why after reading [Overview](overview.md).
- [x] A fresher can follow [Usage](usage.md) to track events from FE/BE and use admin/CLI.
- [x] A developer can locate classes and flows using [Code structure](code-structure.md) and [Request lifecycle](request-lifecycle.md).
- [x] Diagrams use Mermaid and describe ingest, flush, and admin paths.
- [x] Technical terms are defined in the [Overview glossary](overview.md#glossary-analytics) or [Data model](data-model.md).
