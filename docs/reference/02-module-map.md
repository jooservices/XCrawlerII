# 02 - Module Map

This document explains the difference between Core and feature modules, shows an example module tree, and describes how to add a new module. It is descriptive; the rules are in [docs/architecture/01-module-boundaries.md](../architecture/01-module-boundaries.md).

---

## Core vs feature modules

| Type                                                 | Purpose                                                                                                                                      | Allowed to depend on                              |
| ---------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------- |
| **Core** (`Modules/Core`)                            | Shared, domain-agnostic layer: contracts, base classes, shared DTOs/enums, master layout, shared Vue components, single global `httpClient`. | Only itself, framework, and third-party packages. |
| **Feature** (e.g. `Modules/Auth`, `Modules/Crawler`) | Implements a bounded feature: routes, controllers, requests, services, repositories, models, pages/components/tests.                         | **Only Core.** Not other feature modules.         |

Inter-feature communication goes through **contracts/interfaces** (and optionally DTOs) defined in Core; features implement or consume those contracts.

---

## Example module tree

```
Modules/
├── Core/
│   ├── app/
│   │   ├── Contracts/          # Interfaces for cross-cutting or inter-feature use
│   │   ├── Models/
│   │   │   └── MongoDb/         # MongoDB base + Core-owned Mongo models
│   │   ├── Repositories/
│   │   ├── Services/
│   │   └── ...
│   ├── resources/
│   │   └── js/
│   │       ├── MasterLayout.vue
│   │       ├── httpClient.ts
│   │       └── components/      # Shared Vue components
│   └── tests/
│       └── TestCase.php        # Base test case for module tests
├── Auth/                        # Feature: authentication
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Services/
│   │   ├── Models/              # SQL models for Auth
│   │   └── ...
│   └── resources/js/
├── Crawler/                     # Feature: crawling pipeline
│   ├── app/
│   │   ├── Models/
│   │   │   └── MongoDb/         # Crawler-specific MongoDB models
│   │   └── ...
│   └── resources/js/
└── Search/                      # Feature: search API (e.g. Elasticsearch)
    ├── app/
    └── resources/js/
```

- **Core** has no submodules; it is a single shared module.
- **Feature** modules may have `Models/` and, for MongoDB, `Models/MongoDb/`. They use Core for shared layout, http client, and contracts.

---

## How to add a new module

Steps below describe the current process; they do not define new policy. For rules (e.g. no feature→feature deps), see [01-module-boundaries](../architecture/01-module-boundaries.md).

1. **Create the module skeleton**  
   Use the Laravel modules package command (e.g. `php artisan module:make MyFeature`) or copy an existing feature module and rename. Ensure the module lives under `Modules/<Name>/`.

2. **Register the module**  
   Ensure the module is registered (package config or discovery). Confirm autoload and routes are loaded (e.g. route service provider, `modules.php` or equivalent).

3. **Respect dependencies**  
   The new module may **only** depend on `Modules\Core\*` (and framework/vendor). It must **not** depend on other feature modules. If it needs to interact with another feature, introduce a contract (interface) in Core and implement/consume it.

4. **Backend structure**  
   Add Controllers, FormRequests, Services, Repositories, Models following [02-backend-layering](../architecture/02-backend-layering.md) and [03-data-model-standards](../architecture/03-data-model-standards.md). MongoDB models must extend `\Modules\Core\app\Models\MongoDb` and live in `Modules/<Module>/app/Models/MongoDb/`.

5. **Frontend structure**  
   Put feature-specific pages, components, composables, and services under `Modules/<Module>/resources/js/`. Use Core’s `MasterLayout` and `httpClient`; do not add a second “shared” layout or global client used by other features.

6. **Tests**  
   Put tests under `Modules/<Module>/tests/` and extend `Modules\Core\Tests\TestCase`. Follow [05-testing-standards](../architecture/05-testing-standards.md).

7. **Routes**  
   Register routes in the module (e.g. `Modules/<Module>/routes/web.php` or `api.php`) and ensure they are loaded by the app.

---

## References

- [01-module-boundaries](../architecture/01-module-boundaries.md) — Rule IDs MOD-001–MOD-004; dependency diagram
- [00-system-overview](00-system-overview.md) — System and data stores
- [diagrams/module-deps.mmd](diagrams/module-deps.mmd) — Mermaid module dependency diagram
