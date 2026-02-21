# JAV & Core — Route & Layout Refactoring Plan

> **Type:** Handover Document (for any developer to pick up and execute)
> **Source:** Gemini Antigravity brain `98cf114b` + codebase audit 2026-02-21
> **Branch:** `fix/pr-2-ai-feedback-backend` (or new feature branch per phase)
> **Rule:** NO implementation until the relevant Phase is marked **In Progress** by the assigned developer.

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Current State Audit — What Exists Today](#2-current-state-audit)
3. [Target Architecture — Where We Are Going](#3-target-architecture)
4. [Visual Layout — Current vs Target (GAP Analysis)](#4-visual-layout-gap-analysis)
5. [Phase 0 — Centralize the Master App Shell](#phase-0)
6. [Phase 1 — Migrate Authentication & Root APIs to Core](#phase-1)
7. [Phase 2 — Standardize JAV Routes](#phase-2)
8. [Phase 3 — Migrate Generic Features to Core](#phase-3)
9. [Phase 4 — Migrate Admin Features to Core](#phase-4)
10. [Developer Status Tracking](#developer-status-tracking)
11. [Appendix A — File Inventory](#appendix-a)
12. [Appendix B — Route Inventory](#appendix-b)

---

## 1. Executive Summary

### WHAT
Refactor the application so that the **Core module** owns the foundational shell (layouts, auth, generic features) while **JAV** remains a pluggable content module that renders *inside* Core's shell.

### WHY
Today, the JAV module owns everything — master layout, auth, sidebar, navbar, footer, generic features (likes, watchlist, ratings, notifications). This means:
- Adding a second module (e.g., Blog, Shop) would require duplicating the entire layout & auth system.
- Generic features (Like, Watchlist, Rating, Notifications) are trapped inside JAV and cannot be reused.
- Admin tools (Analytics, Telemetry) are JAV-scoped but are actually system-wide.
- The `app.blade.php` Vite entry hardcodes `Modules/JAV/resources/js/app.js`.

### HOW (High Level)
Five sequential phases, each with a clear Definition of Done. Each phase produces a working, deployable state before the next begins.

---

## 2. Current State Audit

### 2.1 Technology Stack
| Layer | Technology |
|---|---|
| Backend | Laravel 11 + nwidart/laravel-modules |
| Frontend | Vue 3 + Inertia.js (SPA-like, server-side routing) |
| State | Pinia (`@jav/Stores/ui`) |
| Data Fetching | Axios + TanStack Vue Query |
| UI Components | PrimeVue (Aura theme) |
| Charts | ApexCharts (vue3-apexcharts) |
| CSS | Custom design system (`dashboard-shared.css`, 985 lines) + Bootstrap 5 CDN |
| Build | Vite 7 |
| DB | MySQL (relational) + MongoDB (analytics/metrics) |
| Search | Elasticsearch via Laravel Scout |
| Queue | Laravel Horizon (Redis) |
| Auth | Session-based (Laravel built-in) + Spatie Permission (RBAC) |

### 2.2 Module Structure
```
Modules/
├── Core/                          # Skeleton — mostly empty frontend
│   ├── app/Http/Controllers/      # CoreController, CurationController
│   ├── app/Services/              # AnalyticsIngestService, etc.
│   ├── resources/views/           # index.blade.php (placeholder), no layouts
│   ├── resources/js/Services/     # analyticsService.js only
│   ├── routes/web.php             # Core resource + Curation API only
│   └── routes/api.php             # Sanctum-guarded JAV API resource
│
├── JAV/                           # OWNS EVERYTHING currently
│   ├── resources/js/Layouts/      # DashboardLayout.vue, GuestLayout.vue
│   ├── resources/js/Layouts/Partials/  # Navbar.vue, Sidebar.vue, Footer.vue
│   ├── resources/js/Stores/ui.js  # Pinia UI store (sidebar state, toast)
│   ├── resources/js/Pages/        # All 20+ page components
│   ├── resources/js/Components/   # MovieCard, ActorCard, UI/*, Search/*
│   ├── resources/css/dashboard-shared.css  # Entire design system (985 lines)
│   ├── routes/web.php             # ALL web routes (160 lines, auth + pages + APIs)
│   └── app/Http/Controllers/      # ALL controllers (Guest, Users, Admin, Api)
```

### 2.3 Entry Point Chain
```
resources/views/app.blade.php          ← Blade root (loads Bootstrap CDN + FA CDN)
  └── @vite(['Modules/JAV/resources/js/app.js'])   ← HARDCODED to JAV
        └── Inertia createApp()
              └── DashboardLayout.vue  (from JAV/resources/js/Layouts/)
                    ├── Navbar.vue     (from JAV/resources/js/Layouts/Partials/)
                    ├── Sidebar.vue    (from JAV/resources/js/Layouts/Partials/)
                    ├── <slot />       (Inertia page component)
                    └── Footer.vue     (from JAV/resources/js/Layouts/Partials/)
```

---

## 3. Target Architecture

### 3.1 Module Responsibility Split

| Concern | Owner (Target) | Owner (Current) |
|---|---|---|
| `app.blade.php` | Core | Root `resources/` |
| `DashboardLayout.vue` | Core | JAV |
| `GuestLayout.vue` | Core | JAV |
| Navbar, Sidebar, Footer | Core | JAV |
| Pinia UI Store (`ui.js`) | Core | JAV |
| CSS Design System | Core | JAV |
| Auth (Login, Register, Logout) | Core | JAV |
| Likes, Watchlist, Ratings, Notifications | Core | JAV |
| Analytics, Telemetry, Search Quality | Core | JAV |
| Movie/Actor/Tag pages & APIs | JAV (stays) | JAV |
| MovieCard, ActorCard, TagCard | JAV (stays) | JAV |

### 3.2 Target Vite Entry
```
@vite(['Modules/Core/resources/js/app.js'])   ← Core owns the entry
```
Core's `app.js` will register the Inertia app and resolve page components from **all** modules via a module-aware resolver.

### 3.3 Target Import Aliases
```js
// vite.config.js aliases (target)
'@core':  'Modules/Core/resources/js'
'@jav':   'Modules/JAV/resources/js'    // already exists
```

---

## 4. Visual Layout — GAP Analysis

### 4.1 Target Layout (from plan)

```
┌─────────────────────────────────────────────────────────────┐
│  NAVBAR (fixed top, full width)                             │
│  ┌──────────┬───────┬───────────┬──────────┬──────┬───────┐ │
│  │left-tool │ logo  │ brandname │ topmenu  │center│r-tool │ │
│  │(toggle)  │       │"XCrawler" │(auth)    │search│(auth) │ │
│  └──────────┴───────┴───────────┴──────────┴──────┴───────┘ │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────┬───────────────────────────────────────────┐ │
│  │  SIDEBAR    │  MAIN CONTENT                            │ │
│  │             │                                          │ │
│  │  [General]  │  [General]   ← Guest + Auth              │ │
│  │  Guest+Auth │  [User]      ← Auth Only                 │ │
│  │             │  [Admin]     ← Admin Only                │ │
│  │  [User]     │                                          │ │
│  │  Auth Only  │                                          │ │
│  │             │                                          │ │
│  │  [Admin]    │                                          │ │
│  │  Admin Only │                                          │ │
│  └─────────────┴───────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│  FOOTER (full width, below both sidebar and main)           │
│  Spans entire width — NOT inside main content column        │
└─────────────────────────────────────────────────────────────┘
```

### 4.2 Current Layout (actual code)

```
┌─────────────────────────────────────────────────────────────┐
│  NAVBAR (fixed top, full width) — ui-navbar                 │
│  ┌──────────┬───────────┬──────────────┬──────┬───────────┐ │
│  │ toggle   │ XCrawler  │ Dashboard    │search│ bell user │ │
│  │ (bars)   │ (brand)   │ Admin▼       │ bar  │ menu      │ │
│  └──────────┴───────────┴──────────────┴──────┴───────────┘ │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────┬───────────────────────────────────────────┐ │
│  │  SIDEBAR    │  MAIN CONTENT                            │ │
│  │  (all auth) │                                          │ │
│  │             │  <slot /> (Inertia page)                 │ │
│  │  Movies     │                                          │ │
│  │  Actors     │                                          │ │
│  │  Tags       │                                          │ │
│  │  ─────────  │                                          │ │
│  │  Recommend  │                                          │ │
│  │  Watchlist  │                                          │ │
│  │  Favorites  │                                          │ │
│  │  History    │  ┌──────────────────────────────────────┐ │ │
│  │  Ratings    │  │  FOOTER (INSIDE main column only!)   │ │ │
│  │             │  │  © 2026 JAV Dashboard                │ │ │
│  │             │  └──────────────────────────────────────┘ │ │
│  └─────────────┴───────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 4.3 GAP Summary

| Area | Current State | Target State | Gap |
|---|---|---|---|
| **Footer placement** | Inside `<main>` column (only spans right side) | Below both sidebar & main (full-width) | **WRONG** — must move `<Footer />` outside the `.ui-row` |
| **Sidebar tiers** | Single flat list, all auth-only | 3 tiers: General (guest+auth), User (auth), Admin (admin) | **MISSING** — no tier separation, no guest section, no admin section |
| **Main Content tiers** | Single `<slot />` | 3 conceptual tiers | OK — tiers are enforced at route/controller level, not layout |
| **Navbar positions** | Roughly correct (~5 sections) | 6 named slot positions | **MINOR** — semantically present but not abstracted as named slots |
| **Layout ownership** | All in JAV module | All in Core module | **MAJOR** — nothing exists in Core's frontend yet |
| **app.blade.php location** | `resources/views/app.blade.php` (root) | `Modules/Core/resources/views/app.blade.php` | **NEEDS MOVE** |
| **Vite entry** | Hardcoded `Modules/JAV/resources/js/app.js` | `Modules/Core/resources/js/app.js` | **NEEDS CHANGE** |
| **UI Store** | `@jav/Stores/ui` | `@core/Stores/ui` | **NEEDS MOVE** |
| **CSS design system** | `JAV/resources/css/dashboard-shared.css` | `Core/resources/css/dashboard-shared.css` | **NEEDS MOVE** |
| **Auth routes** | Defined in JAV `routes/web.php` | Core `routes/web.php` + Core `routes/api.php` | **NEEDS MOVE** |
| **API prefix** | Mixed: `/jav/api/*`, `/watchlist/*`, `/ratings/*` | All `/api/v1/*` | **NEEDS STANDARDIZE** |
| **Route constants** | `JAV_RATINGS_RATING_PATH`, `JAV_NOTIFICATIONS_PATH` | Removed (inline) | **NEEDS CLEANUP** |

---

## Phase 0

## Phase 0 — Centralize the Master App Shell

> **Estimated scope:** ~15 files to move/modify

### WHAT
Transfer the foundational layout shell (Blade root, Vue master layouts, Navbar, Sidebar, Footer, UI Store, CSS design system) from JAV into Core. After this phase, JAV page components render **inside** a Core-provided layout.

### WHY
The "platform frame" (navbar, sidebar, footer, dark theme) is not JAV-specific. It is the application shell that any module should render within. Currently JAV owns it, making it impossible to add a second module without duplicating the entire UI.

### HOW — Step-by-Step

#### Task 0.1 — Move `app.blade.php` to Core
| Detail | Value |
|---|---|
| **Source** | `resources/views/app.blade.php` |
| **Destination** | `Modules/Core/resources/views/app.blade.php` |
| **Action** | Move file. Update `config/view.php` or Core's `ServiceProvider` to register Core's view path. Change `@vite` entry from `Modules/JAV/resources/js/app.js` to `Modules/Core/resources/js/app.js`. |
| **DoD** | `php artisan view:clear && php artisan serve` → app loads without error. Vite resolves Core entry. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 0.2 — Create Core's `app.js` entry point
| Detail | Value |
|---|---|
| **File** | `Modules/Core/resources/js/app.js` |
| **Action** | Create new file. Copy Inertia bootstrap from `Modules/JAV/resources/js/app.js`. Modify the `resolveComponent` function to search pages in ALL modules (Core first, then JAV). Register PrimeVue, Pinia, global plugins. |
| **Key Logic** | The page resolver must handle paths like `JAV/Pages/Dashboard/Index` and `Core/Pages/Admin/Analytics`. Use a convention: page names prefixed with module name. |
| **DoD** | All existing JAV pages still render correctly through the new Core entry. No 404s. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 0.3 — Move Vue Layout files to Core
| Detail | Value |
|---|---|
| **Files to move** | |
| | `JAV/resources/js/Layouts/DashboardLayout.vue` → `Core/resources/js/Layouts/DashboardLayout.vue` |
| | `JAV/resources/js/Layouts/GuestLayout.vue` → `Core/resources/js/Layouts/GuestLayout.vue` (if exists) |
| | `JAV/resources/js/Layouts/Partials/Navbar.vue` → `Core/resources/js/Layouts/Partials/Navbar.vue` |
| | `JAV/resources/js/Layouts/Partials/Sidebar.vue` → `Core/resources/js/Layouts/Partials/Sidebar.vue` |
| | `JAV/resources/js/Layouts/Partials/Footer.vue` → `Core/resources/js/Layouts/Partials/Footer.vue` |
| **Action** | Move files. Update all `import` paths in the layout files themselves (they currently use relative imports so internal references stay the same, but any `@jav/` imports must change to `@core/`). |
| **DoD** | Layouts render from Core. JAV pages still work. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 0.4 — Move Pinia UI Store to Core
| Detail | Value |
|---|---|
| **Source** | `Modules/JAV/resources/js/Stores/ui.js` |
| **Destination** | `Modules/Core/resources/js/Stores/ui.js` |
| **Action** | Move file. Update all imports from `@jav/Stores/ui` to `@core/Stores/ui` in DashboardLayout, Navbar, Sidebar, and any page that uses it. |
| **DoD** | Sidebar toggle, mobile sidebar, toast notifications all work. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 0.5 — Move CSS Design System to Core
| Detail | Value |
|---|---|
| **Source** | `Modules/JAV/resources/css/dashboard-shared.css` (985 lines) |
| **Destination** | `Modules/Core/resources/css/dashboard-shared.css` |
| **Action** | Move file. Update `vite.config.js` entry to reference Core's CSS path. |
| **DoD** | All styling renders correctly. No broken CSS. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 0.6 — Update `vite.config.js` aliases
| Detail | Value |
|---|---|
| **File** | `vite.config.js` |
| **Action** | Add `@core` alias pointing to `Modules/Core/resources/js`. Ensure `@jav` still works. Update entries array to point to Core's `app.js` and Core's `dashboard-shared.css`. |
| **DoD** | `npm run build` succeeds. `npm run dev` hot-reloads correctly. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 0.7 — Fix Footer Placement (Layout Bug)
| Detail | Value |
|---|---|
| **File** | `Core/resources/js/Layouts/DashboardLayout.vue` (after move) |
| **Problem** | Footer is currently INSIDE the `<main>` column. It only spans the right portion of the page (main content area). It should span full width below both sidebar and main. |
| **Current code** | See lines 114-125 of current DashboardLayout.vue: `<Footer />` is inside `<main>` |
| **Target structure** | |

```html
<!-- TARGET DashboardLayout.vue template -->
<div>
    <Toast position="top-right" />
    <Navbar />
    <div class="ui-container-fluid dashboard-layout">
        <div class="ui-row ui-g-0">
            <aside id="sidebarColumn" class="...">
                <Sidebar />
            </aside>
            <main id="mainContentColumn" class="...">
                <slot />
            </main>
        </div>
        <!-- FOOTER MOVED OUTSIDE the .ui-row, spans full width -->
        <Footer />
    </div>
</div>
```

| **CSS changes needed** | Update `footer` styles in `dashboard-shared.css`: remove margin-top, ensure it sits below the flex row and spans 100% width. Adjust `min-height` of `.dashboard-layout` to account for footer. |
| **DoD** | Footer visually spans full width below both sidebar and main content. Responsive: footer still looks correct on mobile. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 0.8 — Add Sidebar Auth Tiers
| Detail | Value |
|---|---|
| **File** | `Core/resources/js/Layouts/Partials/Sidebar.vue` (after move) |
| **Problem** | Currently all sidebar links are shown to all authenticated users in a single flat list. No guest section, no admin section. |
| **Target** | 3 distinct sections with visibility rules: |

```
┌─────────────────┐
│  GENERAL         │  ← Visible to Guest + Auth
│  Movies          │
│  Actors          │
│  Tags            │
├─────────────────┤
│  USER            │  ← Visible to Auth Only
│  Recommendations │
│  Watchlist       │
│  Favorites       │
│  History         │
│  Ratings         │
├─────────────────┤
│  ADMIN           │  ← Visible to Admin Only
│  Analytics       │
│  Telemetry       │
│  Search Quality  │
│  Provider Sync   │
│  Sync Progress   │
└─────────────────┘
```

| **How** | Use Inertia's `usePage().props.auth` to check authentication and role. Wrap each section in `v-if` directives. Accept sidebar items as a **slot/prop system** so modules can register their own items (stretch goal — can hardcode for now). |
| **DoD** | Guest users see only General section. Auth users see General + User. Admin users see all three. Visual dividers between sections. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 0.9 — Update all JAV page imports
| Detail | Value |
|---|---|
| **Scope** | All ~20 page components in `Modules/JAV/resources/js/Pages/` |
| **Action** | Search-and-replace any imports that reference `@jav/Layouts/`, `@jav/Stores/ui`, or relative paths to layout partials. Update them to `@core/Layouts/`, `@core/Stores/ui`. |
| **DoD** | `grep -r "@jav/Layouts" Modules/JAV/` returns zero results. `grep -r "@jav/Stores/ui" Modules/JAV/` returns zero results. All pages render. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

### Phase 0 — Overall DoD
- [ ] JAV pages successfully render inside a Core-loaded Layout.
- [ ] Sidebar and Main Content areas respect the 3-tier visibility logic.
- [ ] Footer spans full width below both sidebar and main content.
- [ ] `npm run build` succeeds with zero errors.
- [ ] `php artisan serve` + `npm run dev` → app loads, all pages work.
- [ ] No references to `@jav/Layouts/` or `@jav/Stores/ui` remain in JAV module.

---

## Phase 1

## Phase 1 — Migrate Authentication & Root APIs to Core

> **Prerequisite:** Phase 0 must be **Done**.

### WHAT
Move all Login, Registration, and Logout logic (Controllers, Views, Routes) from JAV to Core.

### WHY
Users log into the **Platform** (Core), not a specific module (JAV). Auth should be module-agnostic so any future module benefits from the same auth system.

### HOW — Step-by-Step

#### Task 1.1 — Move Auth Controllers to Core
| Detail | Value |
|---|---|
| **Files to move** | |
| | `JAV/app/Http/Controllers/Guest/Auth/LoginController.php` → `Core/app/Http/Controllers/Auth/LoginController.php` |
| | `JAV/app/Http/Controllers/Guest/Auth/RegisterController.php` → `Core/app/Http/Controllers/Auth/RegisterController.php` |
| **Action** | Move files. Update namespace from `Modules\JAV\Http\Controllers\Guest\Auth` to `Modules\Core\Http\Controllers\Auth`. Update any internal references. |
| **DoD** | Controllers exist in Core with correct namespace. No references to old namespace. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 1.2 — Move Auth Vue Pages to Core
| Detail | Value |
|---|---|
| **Files to move** | |
| | `JAV/resources/js/Pages/Auth/Login.vue` → `Core/resources/js/Pages/Auth/Login.vue` |
| | `JAV/resources/js/Pages/Auth/Register.vue` → `Core/resources/js/Pages/Auth/Register.vue` |
| **Action** | Move files. Update any imports that reference JAV-specific paths. |
| **DoD** | Login and Register pages render from Core module. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 1.3 — Define Auth Routes in Core
| Detail | Value |
|---|---|
| **File** | `Modules/Core/routes/web.php` |
| **Routes to add** | |
| | `GET /login` → `Core\Auth\LoginController@showLoginForm` |
| | `POST /login` → `Core\Auth\LoginController@login` |
| | `GET /register` → `Core\Auth\RegisterController@showRegistrationForm` |
| | `POST /register` → `Core\Auth\RegisterController@register` |
| | `POST /logout` → `Core\Auth\LoginController@logout` |
| **Action** | Add routes. Remove corresponding routes from `Modules/JAV/routes/web.php` (lines 150-159). Keep redirect aliases in JAV (`/jav/login` → `/login`) for backward compatibility during transition. |
| **DoD** | `php artisan route:list | grep login` shows routes from Core. Login/Register/Logout work. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 1.4 — Define Auth API Routes (v1 prefix)
| Detail | Value |
|---|---|
| **File** | `Modules/Core/routes/api.php` |
| **Routes to add** | |
| | `POST /api/v1/login` |
| | `POST /api/v1/register` |
| | `POST /api/v1/logout` |
| **Action** | Add API routes for programmatic auth (mobile, SPA). These are **new** — currently auth is form-based only. |
| **DoD** | `curl -X POST /api/v1/login` returns token or session. All paths prefixed with `/api/v1/`. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 1.5 — Update GuestLayout to use Core paths
| Detail | Value |
|---|---|
| **File** | `Core/resources/js/Layouts/GuestLayout.vue` (if exists) |
| **Action** | Update form actions and Inertia links to point to `/login`, `/register`, `/logout` (Core routes, not `/jav/login`). |
| **DoD** | No references to `/jav/login` or `/jav/register` in guest layout. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

### Phase 1 — Overall DoD
- [ ] User can log in/register/logout through Core controllers.
- [ ] All auth submission paths work via both web forms and `/api/v1/` endpoints.
- [ ] JAV module has zero auth controllers or auth routes (only redirect aliases).
- [ ] No regressions — existing users can still log in.

---

## Phase 2

## Phase 2 — Standardize JAV Routes

> **Prerequisite:** Phase 1 must be **Done**.

### WHAT
Organize JAV-specific routes into cleanly separated files. Remove legacy constants. Enforce consistent naming and `/api/v1/jav/` prefix for all internal APIs.

### WHY
Currently JAV's `routes/web.php` (160 lines) mixes page rendering, internal APIs, admin APIs, auth, and legacy aliases. Route constants (`JAV_RATINGS_RATING_PATH`, `JAV_NOTIFICATIONS_PATH`) pollute the global scope. API paths are inconsistent (`/jav/api/*` vs `/watchlist/*` vs `/ratings/*`).

### HOW — Step-by-Step

#### Task 2.1 — Remove Route Constants
| Detail | Value |
|---|---|
| **File** | `Modules/JAV/routes/web.php` |
| **Action** | Remove lines 28-33 (the `define()` calls for `JAV_RATINGS_RATING_PATH` and `JAV_NOTIFICATIONS_PATH`). Replace all usages with inline strings: `'/ratings/{rating}'` and `'/notifications'`. |
| **DoD** | `grep -r "JAV_RATINGS_RATING_PATH\|JAV_NOTIFICATIONS_PATH" Modules/` returns zero results. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 2.2 — Create `JAV/routes/internal_api.php`
| Detail | Value |
|---|---|
| **File (new)** | `Modules/JAV/routes/internal_api.php` |
| **Action** | Extract all Axios-called API routes from `web.php` into this file. These are currently under the `jav/api/*` prefix (lines 78-93 in current web.php). Target prefix: `/api/v1/jav/`. |
| **Routes to move** | |
| | `GET /api/v1/jav/dashboard/items` |
| | `GET /api/v1/jav/search/suggest` |
| | `POST /api/v1/jav/like` |
| | `POST /api/v1/jav/watchlist` (and PUT, DELETE, check) |
| | `POST /api/v1/jav/ratings` (and PUT, DELETE, check) |
| | `GET /api/v1/jav/notifications` (and mark-read endpoints) |
| **DoD** | All internal API routes live in `internal_api.php` with `/api/v1/jav/` prefix. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 2.3 — Update JAV RouteServiceProvider
| Detail | Value |
|---|---|
| **File** | `Modules/JAV/app/Providers/RouteServiceProvider.php` |
| **Action** | Register `internal_api.php` as a new route file with prefix `/api/v1/jav` and middleware `['web', 'auth']`. |
| **DoD** | `php artisan route:list --path=api/v1/jav` lists all internal API routes. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 2.4 — Clean up `JAV/routes/web.php`
| Detail | Value |
|---|---|
| **File** | `Modules/JAV/routes/web.php` |
| **Action** | After moving auth (Phase 1) and internal APIs (Task 2.2), this file should contain ONLY page-rendering routes: dashboard, movies, actors, tags, user pages, admin pages. Remove duplicate route groups (e.g., `/watchlist/*` at lines 132-137, `/ratings/*` at lines 140-148 — these are duplicates of the API routes). |
| **Target size** | ~40-50 lines (down from 160). |
| **DoD** | `web.php` contains only Inertia page routes. No API logic. No auth. No constants. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 2.5 — Update Frontend Axios Calls
| Detail | Value |
|---|---|
| **Scope** | All Vue components that call APIs via `axios` or `route()` |
| **Files likely affected** | MovieCard.vue, ActorCard.vue, Navbar.vue, Dashboard/Index.vue, Watchlist.vue, Ratings pages, Notification pages |
| **Action** | Update `route()` names from `jav.api.*` to the new route names (e.g., `jav.api.v1.dashboard.items`). If using named routes via Ziggy, just update the route names in the backend and the frontend will follow. |
| **DoD** | All Axios calls succeed. No 404s in browser console. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

### Phase 2 — Overall DoD
- [ ] Route constants completely removed.
- [ ] All JAV internal APIs start with `/api/v1/jav/`.
- [ ] `JAV/routes/web.php` contains only page-rendering routes (~40-50 lines).
- [ ] `JAV/routes/internal_api.php` contains all internal API routes.
- [ ] All frontend Axios calls work without errors.
- [ ] No duplicate route definitions.

---

## Phase 3

## Phase 3 — Migrate Generic Features to Core

> **Prerequisite:** Phase 2 must be **Done**.

### WHAT
Move polymorphic interactions — Likes, Watchlist, Ratings, Notifications, Account/Preferences — to Core.

### WHY
These features are generic and polymorphic. A "Like" should work on any model (JAV movie today, Blog post tomorrow). Keeping them in JAV prevents reuse. Notifications are platform-level. Account settings belong to the user, not a content module.

### HOW — Step-by-Step

#### Task 3.1 — Move Library (Likes) Controller + API
| Detail | Value |
|---|---|
| **Source** | `JAV/app/Http/Controllers/Users/Api/LibraryController.php` |
| **Destination** | `Core/app/Http/Controllers/Api/V1/LibraryController.php` |
| **Route** | `POST /api/v1/core/library/likes` (toggle like) |
| **DoD** | Like/Unlike works via Core API. MovieCard.vue calls Core endpoint. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 3.2 — Move Watchlist Controller + API
| Detail | Value |
|---|---|
| **Source** | `JAV/app/Http/Controllers/Users/Api/WatchlistController.php` + `JAV/app/Http/Controllers/Users/WatchlistController.php` |
| **Destination** | `Core/app/Http/Controllers/Api/V1/WatchlistController.php` |
| **Routes** | `POST/PUT/DELETE/GET /api/v1/core/watchlist/*` |
| **DoD** | Watchlist CRUD works via Core API. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 3.3 — Move Rating Controller + API
| Detail | Value |
|---|---|
| **Source** | `JAV/app/Http/Controllers/Users/Api/RatingController.php` + `JAV/app/Http/Controllers/Users/RatingController.php` |
| **Destination** | `Core/app/Http/Controllers/Api/V1/RatingController.php` |
| **Routes** | `POST/PUT/DELETE/GET /api/v1/core/ratings/*` |
| **DoD** | Rating CRUD works via Core API. StarRating component calls Core endpoint. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 3.4 — Move Notification Controller + API
| Detail | Value |
|---|---|
| **Source** | `JAV/app/Http/Controllers/Users/Api/NotificationController.php` + `JAV/app/Http/Controllers/Users/NotificationController.php` |
| **Destination** | `Core/app/Http/Controllers/Api/V1/NotificationController.php` |
| **Routes** | `GET /api/v1/core/notifications`, `POST /api/v1/core/notifications/{id}/read`, `POST /api/v1/core/notifications/read-all` |
| **DoD** | Notifications render in Navbar via Core API. Mark-read works. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 3.5 — Move Preference/Account Controller
| Detail | Value |
|---|---|
| **Source** | `JAV/app/Http/Controllers/Users/PreferenceController.php` |
| **Destination** | `Core/app/Http/Controllers/Users/PreferenceController.php` |
| **Routes** | `POST /api/v1/core/preferences`, `POST/DELETE /api/v1/core/account/avatar`, `PUT /api/v1/core/account/profile`, `PUT /api/v1/core/account/password` |
| **DoD** | Preferences, avatar upload, profile update, password change all work via Core. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 3.6 — Move User-facing Vue Pages to Core
| Detail | Value |
|---|---|
| **Pages to move** | |
| | `JAV/Pages/User/Watchlist.vue` → `Core/Pages/User/Watchlist.vue` |
| | `JAV/Pages/User/Favorites.vue` → `Core/Pages/User/Favorites.vue` |
| | `JAV/Pages/User/History.vue` → `Core/Pages/User/History.vue` |
| | `JAV/Pages/User/Notifications.vue` → `Core/Pages/User/Notifications.vue` |
| | `JAV/Pages/User/Preferences.vue` → `Core/Pages/User/Preferences.vue` |
| | `JAV/Pages/User/Recommendations.vue` → `Core/Pages/User/Recommendations.vue` |
| | `JAV/Pages/Ratings/Index.vue` → `Core/Pages/Ratings/Index.vue` |
| | `JAV/Pages/Ratings/Show.vue` → `Core/Pages/Ratings/Show.vue` |
| **Note** | These pages may import JAV-specific components like `MovieCard.vue`. Keep those imports — the pages render in Core but reference JAV content components. Alternatively, pass content cards as slots. |
| **DoD** | All user pages render from Core module. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 3.7 — Update Frontend Component API Calls
| Detail | Value |
|---|---|
| **Scope** | MovieCard.vue, ActorCard.vue, Navbar.vue (notifications), all moved pages |
| **Action** | Update `route()` calls to point to Core API route names instead of JAV. |
| **DoD** | All interactions (like, watchlist, rate, notify) use `/api/v1/core/*` endpoints. Zero references to old JAV API routes for these features. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

### Phase 3 — Overall DoD
- [ ] Like/Unlike works via `/api/v1/core/library/likes`.
- [ ] Watchlist CRUD works via `/api/v1/core/watchlist/*`.
- [ ] Ratings CRUD works via `/api/v1/core/ratings/*`.
- [ ] Notifications render in Navbar via Core API.
- [ ] Account/Preferences managed via Core.
- [ ] All moved Vue pages render correctly from Core.
- [ ] JAV module only contains JAV-specific logic (movies, actors, tags, dashboard data).

---

## Phase 4

## Phase 4 — Migrate Admin Features to Core

> **Prerequisite:** Phase 3 must be **Done**.

### WHAT
Move admin-only features — Analytics, Job Telemetry, Search Quality, Provider Sync, Sync Progress — to Core.

### WHY
Administration of system-wide data (Redis telemetry, Elasticsearch quality, provider sync health) belongs at the Core/platform level, not inside a content module. Analytics aggregates data across all models, not just JAV.

### HOW — Step-by-Step

#### Task 4.1 — Move Admin Controllers to Core
| Detail | Value |
|---|---|
| **Files to move** | |
| | `JAV/Http/Controllers/Admin/AnalyticsController.php` → `Core/Http/Controllers/Admin/AnalyticsController.php` |
| | `JAV/Http/Controllers/Admin/JobTelemetryController.php` → `Core/Http/Controllers/Admin/JobTelemetryController.php` |
| | `JAV/Http/Controllers/Admin/SearchQualityController.php` → `Core/Http/Controllers/Admin/SearchQualityController.php` |
| | `JAV/Http/Controllers/Admin/SyncController.php` → `Core/Http/Controllers/Admin/SyncController.php` |
| | All corresponding `Admin/Api/*` controllers → `Core/Http/Controllers/Admin/Api/*` |
| **Action** | Move files. Update namespaces. Update service/model references if they point to JAV-specific models (keep those references — Core controllers can depend on JAV models). |
| **DoD** | Controllers in Core, correct namespaces, no broken references. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 4.2 — Move Admin Routes to Core
| Detail | Value |
|---|---|
| **File** | `Modules/Core/routes/web.php` |
| **Routes to add** | |
| | Page routes: `/admin/analytics`, `/admin/job-telemetry`, `/admin/search-quality`, `/admin/provider-sync`, `/admin/sync-progress` |
| | API routes: `/api/v1/admin/analytics/*`, `/api/v1/admin/job-telemetry/*`, `/api/v1/admin/search-quality/*`, `/api/v1/admin/provider-sync/*`, `/api/v1/admin/sync-progress/*` |
| **Action** | Define routes in Core. Remove from JAV `routes/web.php`. |
| **DoD** | `php artisan route:list --path=admin` shows routes from Core. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 4.3 — Move Admin Vue Pages to Core
| Detail | Value |
|---|---|
| **Pages to move** | |
| | `JAV/Pages/Admin/Analytics.vue` → `Core/Pages/Admin/Analytics.vue` |
| | `JAV/Pages/Admin/JobTelemetry.vue` → `Core/Pages/Admin/JobTelemetry.vue` |
| | `JAV/Pages/Admin/SearchQuality.vue` → `Core/Pages/Admin/SearchQuality.vue` |
| | `JAV/Pages/Admin/ProviderSync.vue` → `Core/Pages/Admin/ProviderSync.vue` |
| | `JAV/Pages/Admin/SyncProgress.vue` → `Core/Pages/Admin/SyncProgress.vue` |
| | `JAV/Pages/Admin/Users/*` → Already in root `app/` (keep as-is or move to Core) |
| | `JAV/Pages/Admin/Roles/*` → Already in root `app/` (keep as-is or move to Core) |
| **DoD** | Admin pages render from Core module. All data loads correctly. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 4.4 — Wire Admin Sidebar Block
| Detail | Value |
|---|---|
| **File** | `Core/resources/js/Layouts/Partials/Sidebar.vue` |
| **Action** | Ensure the "Admin" sidebar tier (added in Task 0.8) links to the Core admin pages using the new Core route names. |
| **DoD** | Clicking Analytics/Telemetry/Quality/Sync in the sidebar navigates to Core admin pages. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

#### Task 4.5 — Update Navbar Admin Dropdown
| Detail | Value |
|---|---|
| **File** | `Core/resources/js/Layouts/Partials/Navbar.vue` |
| **Action** | Update the Admin dropdown menu `route()` calls from JAV route names (`jav.vue.admin.analytics`, etc.) to Core route names (`core.admin.analytics`, etc.). |
| **DoD** | Admin dropdown in navbar navigates to Core admin pages. |
| **Status** | `[ ] Not Started` / `[ ] In Progress` / `[ ] Review` / `[ ] Done` |
| **Developer** | _________________________ |
| **Notes** | _________________________ |

### Phase 4 — Overall DoD
- [ ] Admin dashboard and all admin screens load from Core module.
- [ ] Admin sidebar section links to Core admin pages.
- [ ] Navbar Admin dropdown links to Core admin pages.
- [ ] All admin API endpoints use `/api/v1/admin/*` prefix.
- [ ] JAV module has zero admin controllers or admin routes.

---

## Developer Status Tracking

**RULE: Every developer MUST update the status of their assigned Task before starting work and upon completion.**

### Status Legend
| Symbol | Meaning |
|---|---|
| `[ ] Not Started` | No work has begun |
| `[~] In Progress` | Developer is actively working on this |
| `[?] Blocked` | Work is blocked — see Notes column |
| `[R] Ready for Review` | Implementation complete, needs code review |
| `[x] Done` | Reviewed, merged, and verified |

### Progress Dashboard

| Phase | Task | Status | Developer | Branch | PR |
|---|---|---|---|---|---|
| P0 | T0.1 Move app.blade.php | `[ ]` | | | |
| P0 | T0.2 Core app.js entry | `[ ]` | | | |
| P0 | T0.3 Move Vue layouts | `[ ]` | | | |
| P0 | T0.4 Move Pinia UI Store | `[ ]` | | | |
| P0 | T0.5 Move CSS design system | `[ ]` | | | |
| P0 | T0.6 Update vite.config.js | `[ ]` | | | |
| P0 | T0.7 Fix Footer placement | `[ ]` | | | |
| P0 | T0.8 Add Sidebar auth tiers | `[ ]` | | | |
| P0 | T0.9 Update JAV imports | `[ ]` | | | |
| P1 | T1.1 Move auth controllers | `[ ]` | | | |
| P1 | T1.2 Move auth Vue pages | `[ ]` | | | |
| P1 | T1.3 Define auth web routes | `[ ]` | | | |
| P1 | T1.4 Define auth API routes | `[ ]` | | | |
| P1 | T1.5 Update GuestLayout | `[ ]` | | | |
| P2 | T2.1 Remove route constants | `[ ]` | | | |
| P2 | T2.2 Create internal_api.php | `[ ]` | | | |
| P2 | T2.3 Update RouteServiceProvider | `[ ]` | | | |
| P2 | T2.4 Clean up web.php | `[ ]` | | | |
| P2 | T2.5 Update frontend Axios calls | `[ ]` | | | |
| P3 | T3.1 Move Likes to Core | `[ ]` | | | |
| P3 | T3.2 Move Watchlist to Core | `[ ]` | | | |
| P3 | T3.3 Move Ratings to Core | `[ ]` | | | |
| P3 | T3.4 Move Notifications to Core | `[ ]` | | | |
| P3 | T3.5 Move Preferences to Core | `[ ]` | | | |
| P3 | T3.6 Move user Vue pages | `[ ]` | | | |
| P3 | T3.7 Update frontend API calls | `[ ]` | | | |
| P4 | T4.1 Move admin controllers | `[ ]` | | | |
| P4 | T4.2 Move admin routes | `[ ]` | | | |
| P4 | T4.3 Move admin Vue pages | `[ ]` | | | |
| P4 | T4.4 Wire admin sidebar | `[ ]` | | | |
| P4 | T4.5 Update navbar admin dropdown | `[ ]` | | | |

---

## Appendix A

## Appendix A — File Inventory (Current Locations)

### Layout Files (ALL in JAV — must move to Core in P0)
```
Modules/JAV/resources/js/Layouts/DashboardLayout.vue     (127 lines)
Modules/JAV/resources/js/Layouts/Partials/Navbar.vue      (477 lines)
Modules/JAV/resources/js/Layouts/Partials/Sidebar.vue     (118 lines)
Modules/JAV/resources/js/Layouts/Partials/Footer.vue      (5 lines)
Modules/JAV/resources/js/Stores/ui.js                     (51 lines)
Modules/JAV/resources/css/dashboard-shared.css            (985 lines)
resources/views/app.blade.php                             (28 lines)
```

### Auth Files (ALL in JAV — must move to Core in P1)
```
Modules/JAV/app/Http/Controllers/Guest/Auth/LoginController.php
Modules/JAV/app/Http/Controllers/Guest/Auth/RegisterController.php
Modules/JAV/resources/js/Pages/Auth/Login.vue
Modules/JAV/resources/js/Pages/Auth/Register.vue
```

### Generic Feature Files (ALL in JAV — must move to Core in P3)
```
Modules/JAV/app/Http/Controllers/Users/Api/LibraryController.php
Modules/JAV/app/Http/Controllers/Users/Api/WatchlistController.php
Modules/JAV/app/Http/Controllers/Users/Api/RatingController.php
Modules/JAV/app/Http/Controllers/Users/Api/NotificationController.php
Modules/JAV/app/Http/Controllers/Users/WatchlistController.php
Modules/JAV/app/Http/Controllers/Users/RatingController.php
Modules/JAV/app/Http/Controllers/Users/NotificationController.php
Modules/JAV/app/Http/Controllers/Users/PreferenceController.php
```

### Admin Files (ALL in JAV — must move to Core in P4)
```
Modules/JAV/app/Http/Controllers/Admin/AnalyticsController.php
Modules/JAV/app/Http/Controllers/Admin/JobTelemetryController.php
Modules/JAV/app/Http/Controllers/Admin/SearchQualityController.php
Modules/JAV/app/Http/Controllers/Admin/SyncController.php
Modules/JAV/app/Http/Controllers/Admin/Api/AnalyticsController.php
Modules/JAV/app/Http/Controllers/Admin/Api/JobTelemetryController.php
Modules/JAV/app/Http/Controllers/Admin/Api/SearchQualityController.php
Modules/JAV/app/Http/Controllers/Admin/Api/SyncController.php
```

### Files That STAY in JAV (not moved)
```
Modules/JAV/app/Http/Controllers/Users/DashboardController.php
Modules/JAV/app/Http/Controllers/Users/JAVController.php
Modules/JAV/app/Http/Controllers/Users/MovieController.php
Modules/JAV/app/Http/Controllers/Users/Api/DashboardController.php
Modules/JAV/app/Http/Controllers/Users/Api/SearchSuggestController.php
Modules/JAV/resources/js/Pages/Dashboard/*
Modules/JAV/resources/js/Pages/Movies/*
Modules/JAV/resources/js/Pages/Actors/*
Modules/JAV/resources/js/Pages/Tags/*
Modules/JAV/resources/js/Components/MovieCard.vue
Modules/JAV/resources/js/Components/ActorCard.vue
Modules/JAV/resources/js/Components/TagCard.vue
Modules/JAV/resources/js/Components/UI/*          (generic UI components — could move to Core later)
Modules/JAV/resources/js/Components/Search/*
```

---

## Appendix B

## Appendix B — Route Inventory (Current → Target)

### Auth Routes
| Current Path | Current Location | Target Path | Target Location |
|---|---|---|---|
| `GET /login` | JAV web.php:153 | `GET /login` | Core web.php |
| `POST /login` | JAV web.php:154 | `POST /login` | Core web.php |
| `GET /register` | JAV web.php:155 | `GET /register` | Core web.php |
| `POST /register` | JAV web.php:156 | `POST /register` | Core web.php |
| `POST /logout` | JAV web.php:159 | `POST /logout` | Core web.php |
| `GET /jav/login` | JAV web.php:151 | Redirect → `/login` | JAV web.php (alias) |
| `GET /jav/register` | JAV web.php:152 | Redirect → `/register` | JAV web.php (alias) |

### Internal API Routes (JAV-specific)
| Current Path | Target Path |
|---|---|
| `GET /jav/api/dashboard/items` | `GET /api/v1/jav/dashboard/items` |
| `GET /jav/api/search/suggest` | `GET /api/v1/jav/search/suggest` |

### Generic Feature API Routes (move to Core)
| Current Path | Target Path |
|---|---|
| `POST /jav/api/like` | `POST /api/v1/core/library/likes` |
| `POST /jav/api/watchlist` | `POST /api/v1/core/watchlist` |
| `PUT /jav/api/watchlist/{id}` | `PUT /api/v1/core/watchlist/{id}` |
| `DELETE /jav/api/watchlist/{id}` | `DELETE /api/v1/core/watchlist/{id}` |
| `GET /jav/api/watchlist/check/{id}` | `GET /api/v1/core/watchlist/check/{id}` |
| `POST /jav/api/ratings` | `POST /api/v1/core/ratings` |
| `PUT /jav/api/ratings/{id}` | `PUT /api/v1/core/ratings/{id}` |
| `DELETE /jav/api/ratings/{id}` | `DELETE /api/v1/core/ratings/{id}` |
| `GET /jav/api/notifications` | `GET /api/v1/core/notifications` |
| `POST /jav/api/notifications/{id}/read` | `POST /api/v1/core/notifications/{id}/read` |
| `POST /jav/api/notifications/read-all` | `POST /api/v1/core/notifications/read-all` |

### Duplicate Routes (to be REMOVED)
| Path | Location | Reason |
|---|---|---|
| `POST /watchlist` | JAV web.php:133 | Duplicate of `/jav/api/watchlist` |
| `PUT /watchlist/{id}` | JAV web.php:134 | Duplicate |
| `DELETE /watchlist/{id}` | JAV web.php:135 | Duplicate |
| `GET /watchlist/check/{id}` | JAV web.php:136 | Duplicate |
| `POST /ratings` | JAV web.php:143 | Duplicate of `/jav/api/ratings` |
| `PUT /ratings/{id}` | JAV web.php:144 | Duplicate |
| `DELETE /ratings/{id}` | JAV web.php:145 | Duplicate |
| `GET /ratings/check/{id}` | JAV web.php:141 | Duplicate |
| `POST /jav/like` | JAV web.php:102 | Duplicate of `/jav/api/like` |
| `GET /jav/notifications` | JAV web.php:101 | Legacy alias (duplicate) |
| `POST /jav/notifications/{id}/read` | JAV web.php:110 | Duplicate |
| `POST /jav/notifications/read-all` | JAV web.php:111 | Duplicate |

### Admin API Routes (move to Core)
| Current Path | Target Path |
|---|---|
| `GET /jav/admin/analytics/distribution-data` | `GET /api/v1/admin/analytics/distribution-data` |
| `GET /jav/admin/analytics/association-data` | `GET /api/v1/admin/analytics/association-data` |
| `GET /jav/admin/analytics/trends-data` | `GET /api/v1/admin/analytics/trends-data` |
| `GET /jav/admin/analytics/overview-data` | `GET /api/v1/admin/analytics/overview-data` |
| `GET /jav/admin/analytics/quality-data` | `GET /api/v1/admin/analytics/quality-data` |
| `GET /jav/admin/analytics/actor-insights` | `GET /api/v1/admin/analytics/actor-insights` |
| `POST /jav/admin/analytics/predict` | `POST /api/v1/admin/analytics/predict` |
| `GET /jav/admin/analytics/suggest` | `GET /api/v1/admin/analytics/suggest` |
| `GET /jav/admin/provider-sync/status` | `GET /api/v1/admin/provider-sync/status` |
| `GET /jav/admin/sync-progress/data` | `GET /api/v1/admin/sync-progress/data` |
| `POST /jav/admin/search-quality/preview` | `POST /api/v1/admin/search-quality/preview` |
| `POST /jav/admin/search-quality/publish` | `POST /api/v1/admin/search-quality/publish` |
| `POST /jav/admin/provider-sync/dispatch` | `POST /api/v1/admin/provider-sync/dispatch` |
| `GET /admin/job-telemetry` | `GET /admin/job-telemetry` (stays, just Core-owned) |
| `GET /admin/job-telemetry/summary-data` | `GET /api/v1/admin/job-telemetry/summary-data` |

---

*End of document. This plan is the single source of truth for the refactoring effort. All developers must update their task status in the Progress Dashboard above before starting and after completing work.*
