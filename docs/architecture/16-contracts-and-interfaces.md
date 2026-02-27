# 16 - Contracts and Interfaces (Module-wide)

## Purpose

This standard defines where and how PHP interfaces (contracts) MUST be placed and named in **any** module. A single canonical path per module (`*/Contracts/`) and consistent naming reduce drift, simplify discovery, and make PR review and code generation predictable.

## Scope

- **In scope:** All interfaces owned by a module, in any module (Core, Inventory, Analytics, Billing, etc.).
- **Out of scope:** PSR/third-party interfaces (e.g. `Psr\*`).

## Rules

### Location

Rule `16-CON-001`:
All module interfaces MUST be placed under `Modules/{Module}/app/Contracts/`. No other `Contracts` path is allowed.

Rationale:
One canonical place per module avoids scattered `Contracts` folders and duplicate naming.

Allowed:

- Flat: `Modules/Inventory/app/Contracts/StockRepositoryInterface.php`
- Grouped (one level): `Modules/Analytics/app/Contracts/Repositories/EventRepositoryInterface.php`

Forbidden (MUST NOT):

- `Modules/{Module}/app/Services/Contracts/...`
- `Modules/{Module}/app/Repositories/Contracts/...`
- Any `Contracts` folder outside `Modules/{Module}/app/Contracts/`

Verification:

- `rg --files 'Contracts/' Modules/` returns only paths under `Modules/*/app/Contracts/`.

---

Rule `16-CON-002`:
Group subfolders inside `Contracts/` are OPTIONAL. When used, group folder names MUST be plural and MUST be one level only (no nested groups).

Rationale:
Plural group names align with Laravel conventions; one-level grouping keeps the tree simple and enforceable.

Allowed:

- `Contracts/Repositories/ConfigRepositoryInterface.php`
- `Contracts/Services/ConfigServiceInterface.php`
- `Contracts/Clients/HttpClientInterface.php`

Forbidden:

- `Contracts/Repository/...` (singular)
- `Contracts/Services/Client/ClientInterface.php` (nested group; use `Contracts/Clients/ClientInterface.php` or flat)

Verification:

- Any directory under `Contracts/` is plural and one level deep only.

---

### Naming

Rule `16-CON-003`:
Interface names MUST be StudlyCase and MUST end with the suffix `Interface`. The file name MUST match the interface name 1:1 (one interface per file). The `I*` prefix style (e.g. `IConfigRepository`) is forbidden.

Rationale:
Immediate recognition and consistent autoloading; no ambiguity with concrete class names.

Allowed:

- `ConfigRepositoryInterface` in file `ConfigRepositoryInterface.php`
- `HttpClientInterface` in file `HttpClientInterface.php`

Forbidden:

- `ConfigRepository` (missing `Interface` suffix)
- `IConfigRepository` (prefix style not allowed)
- `ClientContract` for new code (see migration guidance below)
- Two interfaces in one file

Verification:

- Filename equals `{InterfaceName}.php`; interface name ends with `Interface`.

---

Rule `16-CON-004`:
Repository (and other) interface names MUST be semantic (e.g. `ConfigRepositoryInterface`, `EventRepositoryInterface`). Vague base names (e.g. `RepositoryInterface`) are allowed only when they define a true shared base contract used by multiple implementations in that module.

Rationale:
Semantic names improve discoverability and intent clarity.

Allowed:

- `ConfigRepositoryInterface`, `LogRepositoryInterface`, `EventRepositoryInterface`

Forbidden (unless truly a shared base):

- `RepositoryInterface` as the only repository contract in the module

Verification:

- Names reflect the aggregate or capability (Config, Log, Event, etc.).

---

### Migration: `*Contract` → `*Interface`

Rule `16-CON-005`:
New interfaces MUST use the `Interface` suffix and MUST NOT be named `*Contract`. Existing code using `*Contract` MUST, when touched, be migrated: rename to `*Interface` and move the file under `Modules/{Module}/app/Contracts/` (with optional group). Until migration, any bypass MUST be registered in `11-exceptions-registry.md` with rule ref `16-CON-*`.

---

## Folder Layout Examples

**Correct (flat):**

```text
Modules/Inventory/app/Contracts/
├── StockRepositoryInterface.php
└── ReservationInterface.php
```

**Correct (grouped, one level):**

```text
Modules/Analytics/app/Contracts/
├── Repositories/
│   ├── EventRepositoryInterface.php
│   └── MetricRepositoryInterface.php
└── Writers/
    └── ReportWriterInterface.php
```

**Incorrect:**

```text
❌ Modules/Billing/app/Services/Contracts/PaymentGatewayContract.php
❌ Modules/Core/app/Repositories/Contracts/ConfigRepositoryInterface.php
❌ Modules/Analytics/app/Contracts/Repository/EventRepositoryInterface.php   (singular group)
❌ Modules/Analytics/app/Contracts/Services/Client/ClientInterface.php     (nested group)
```

---

## Naming Examples

| Intent            | ✅ Correct                      | ❌ Incorrect                       |
| ----------------- | ------------------------------- | ---------------------------------- |
| Config repository | `ConfigRepositoryInterface`     | `ConfigRepository`, `IConfigRepo`  |
| HTTP client       | `HttpClientInterface`           | `ClientContract`, `IHttpClient`    |
| Report writer     | `ReportWriterInterface`         | `ReportWriter`                     |
| File name         | `ConfigRepositoryInterface.php` | `ConfigRepository.php` (no suffix) |

---

## Interface → Implementation → Binding

**Canonical mapping (within the owning module):**

- **Interface:** `Modules/Inventory/app/Contracts/Repositories/StockRepositoryInterface.php`
- **Concrete:** `Modules/Inventory/app/Repositories/StockRepository.php`
- **Binding:** In that module’s service provider (e.g. `InventoryServiceProvider::register()`): `$this->app->singleton(StockRepositoryInterface::class, StockRepository::class);`

Example:

```php
// Modules/Inventory/app/Contracts/Repositories/StockRepositoryInterface.php
namespace Modules\Inventory\Contracts\Repositories;

interface StockRepositoryInterface
{
    public function findBySku(string $sku): ?Stock;
    public function reserve(string $sku, int $qty): void;
}
```

```php
// Modules/Inventory/app/Repositories/StockRepository.php
namespace Modules\Inventory\Repositories;

use Modules\Inventory\Contracts\Repositories\StockRepositoryInterface;

class StockRepository implements StockRepositoryInterface
{
    // ...
}
```

```php
// In InventoryServiceProvider::register() (or the owning module’s service provider)
$this->app->singleton(
    \Modules\Inventory\Contracts\Repositories\StockRepositoryInterface::class,
    \Modules\Inventory\Repositories\StockRepository::class
);
```

Consumers type-hint the interface; the container resolves to the concrete implementation.

---

## Enforcement Checklist (PR / AI)

- [ ] Every new or moved interface lives under `Modules/{Module}/app/Contracts/` (optional plural one-level group).
- [ ] No `Contracts` directory exists under `Services/`, `Repositories/`, or elsewhere under any module’s `app/`.
- [ ] Interface name is StudlyCase and ends with `Interface`; file name matches interface name.
- [ ] No `I*` prefix; no new `*Contract` names.
- [ ] Group folders under `Contracts/` are plural and one level only.
- [ ] Repository (and similar) interface names are semantic.
- [ ] Binding is registered in that module’s service provider when the interface is used for DI.

---

## FAQ

**Why not `*/Repositories/Contracts/` or `*/Services/Contracts/`?**  
One canonical `Contracts/` root per module avoids duplicate trees and naming collisions. Implementations stay in Repositories/Services; the contract is the abstraction and belongs in one place per module.

**Why require the `Interface` suffix?**  
Clear naming and file discovery; aligns with common PHP practice and static analysis. No ambiguity with concrete class names.

**When should we create a group folder?**  
When you have several interfaces of the same role (e.g. multiple repositories or clients). For one or two interfaces, a flat `Contracts/` is acceptable.

**What about existing `*Contract` names?**  
When touched, rename to `*Interface` and move under `*/Contracts/`. Until then, register any bypass in `11-exceptions-registry.md`.

**Where do cross-module interfaces go?**  
Interfaces used by more than one module MUST live in Core per `01-module-boundaries-and-dependencies.md`. This doc defines placement and naming **within** any module (including Core).

---

## Exception Link

Exceptions to this document MUST be registered in `11-exceptions-registry.md` with rule ref `16-CON-*`.
