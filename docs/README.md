# XCrawlerII Documentation

This documentation set is updated for business stakeholders, technical leads, and developers.

## Structure

```text
docs/
├── architecture/
│   ├── overview.md
│   ├── system-design.md
│   ├── data-model.md
│   └── request-lifecycle.md
├── analytics/
│   ├── README.md           (analytics index and reading order)
│   ├── overview.md         (what, why, how, glossary, KPIs)
│   ├── usage.md            (FE / BE / admin / CLI usage)
│   ├── code-structure.md   (classes, files, logic)
│   ├── request-lifecycle.md (request flow and diagrams)
│   └── data-model.md       (Redis, Mongo, MySQL for analytics)
├── guides/
│   ├── getting-started.md
│   ├── implementation-guide.md
│   └── code-skeletons/
├── api/
│   └── api-reference.md
├── testing/
│   └── testing-strategy.md
├── deployment/
│   └── deployment-guide.md
└── troubleshooting/
    └── faq.md
```

## Reading Order

1. `architecture/overview.md`
2. `architecture/system-design.md`
3. `architecture/data-model.md`
4. `architecture/request-lifecycle.md`
5. `analytics/README.md` (then analytics sub-docs as needed)
6. `analytics/overview.md`
7. `analytics/usage.md`
8. `analytics/code-structure.md`
9. `analytics/request-lifecycle.md`
10. `analytics/data-model.md` (when working with analytics storage)
11. `guides/getting-started.md`
12. `guides/implementation-guide.md`
13. `api/api-reference.md`
14. `testing/testing-strategy.md`
15. `deployment/deployment-guide.md`
16. `troubleshooting/faq.md`

## Validation Checklist

- [x] Business stakeholder can explain project value after reading `architecture/overview.md`.
- [x] Technical lead can create implementation tickets from architecture and guides.
- [x] Junior developer can run project and start work within one hour.
- [x] Mermaid diagrams are provided for business flow, design, and lifecycle.
- [x] No placeholder markers remain in active docs.
- [x] Technical terms are defined in glossary sections.
- [x] Clarity review loop completed; FAQ includes resulting questions.
