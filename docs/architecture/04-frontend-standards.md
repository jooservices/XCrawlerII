# 04 - Frontend Standards

## Purpose

Define the roles of Inertia Pages, Components, Composables, and Services, and enforce that shared frontend assets live only in Core so that feature modules stay independent and the UI layer is consistent.

## Scope

- All frontend code (Vue 3, TypeScript, Inertia) under `resources/js/` and `Modules/*/resources/js/`.
- Shared assets: layout, http client, shared components.

## Non-goals

- Design system or PrimeVue/FontAwesome usage details (only that they are the standard)
- Build tool configuration (Vite) unless it affects where files live

## Definitions

| Term              | Meaning                                                                                                |
| ----------------- | ------------------------------------------------------------------------------------------------------ |
| **Page**          | Inertia page component; orchestration and layout only; no business logic.                              |
| **Component**     | Presentational Vue component; receives props, emits events; no API calls.                              |
| **Composable**    | Function (often `useX`) that holds UI logic/state; reusable across components/pages.                   |
| **Service**       | API client wrapper only; calls backend via shared `httpClient` or module-specific client.              |
| **Shared assets** | Master layout, shared components, single global `httpClient`, shared composables used across features. |

---

## Stack (project standard)

- **Vue 3** with Composition API; `<script setup lang="ts">` only.
- **Inertia.js** for page binding to Laravel.
- **PrimeVue** and **FontAwesome** for UI; no other component/icon libraries unless approved.
- **Vite** for build.

---

## Rules

### FE-ARCH-001: Pages are orchestration and layout only

**Rule:** Inertia Pages MUST handle only orchestration and layout: composing components, passing props, handling high-level events (e.g. submit → call service). Pages must NOT contain business logic (e.g. complex calculations, validation rules) or direct API implementation details; delegate to Composables and Services.

**Rationale:** Keeps pages readable and testable; logic lives in composables/services.

**Allowed:**

- Page uses a composable: `const { form, submit } = useLoginForm();` and binds to `<LoginForm :form="form" @submit="submit" />`.
- Page calls a service: `await authService.login(credentials);` then `router.visit(...)`.

**Anti-examples (forbidden):**

- Page with 100+ lines of form validation and state machine logic inline.
- Page implementing `axios.post(...)` or raw `fetch` instead of using a service.

**Enforcement:** Code review.  
**References:** [02-backend-layering](02-backend-layering.md) (backend counterpart).

---

### FE-ARCH-002: Components are presentational

**Rule:** Components MUST be presentational: receive props, emit events, render UI. They must NOT call APIs directly, hold global state, or contain feature-level business rules. Data fetching and side-effects belong in Pages/Composables or Services.

**Rationale:** Reusable, testable UI building blocks; single responsibility.

**Allowed:**

- `<DataTable :rows="rows" :columns="columns" @row-click="onRowClick" />`.
- Component uses only props, emits, and local UI state (e.g. open/close).

**Anti-examples (forbidden):**

- Component that imports and calls `httpClient.get('/api/...')`.
- Component that imports a feature-specific service and performs login/signup.

**Enforcement:** Code review.  
**References:** [06-code-review-checklist](06-code-review-checklist.md).

---

### FE-ARCH-003: Composables hold UI logic and state

**Rule:** Composables (e.g. `useLoginForm`, `usePaginatedList`) MUST hold UI logic and state that can be reused across components or pages. They may call Services. They must NOT contain presentational markup; that stays in Components/Pages.

**Rationale:** Shared logic without duplication; clear separation from presentation.

**Allowed:**

- `useLoginForm()` returning `{ form, errors, submit, loading }` and calling `authService.login`.
- `usePaginatedList(fetchFn)` managing page, limit, loading, items.

**Anti-examples (forbidden):**

- Composable that returns raw JSX/Vue template.
- Composable that implements HTTP calls with raw `fetch` instead of a Service.

**Enforcement:** Code review.  
**References:** [01-module-boundaries](01-module-boundaries-and-dependencies.md) (shared composables in Core).

---

### FE-ARCH-004: Services are API client wrappers only

**Rule:** Frontend Services MUST be API client wrappers only: they receive parameters, call the backend (via shared or module-specific http client), and return or expose response data. They must NOT contain UI state or business logic that belongs in the backend.

**Rationale:** Single place for API contracts; backend remains source of truth for business rules.

**Allowed:**

- `authService.login(email, password)` → calls `httpClient.post('/api/auth/login', ...)` and returns result.
- `crawlerService.listJobs(filters)` → calls `httpClient.get('/api/crawler/jobs', { params: filters })`.

**Anti-examples (forbidden):**

- Service that holds Vue refs or reactive state.
- Service that implements validation rules that duplicate backend (beyond basic UX hints).

**Enforcement:** Code review.  
**References:** [02-backend-layering](02-backend-layering.md).

---

### FE-ARCH-005: Shared assets live only in Core

**Rule:** Shared frontend assets (MasterLayout, shared components, single global `httpClient.ts`, shared composables used by more than one feature) MUST live ONLY in `Modules/Core/resources/js/`. Feature modules must NOT define their own “shared” layout or global HTTP client that is used by other features; they may use Core’s shared assets and add feature-specific components/composables/services in their own module.

**Rationale:** Prevents duplication and coupling; one place for cross-cutting UI and HTTP.

**Allowed:**

- `Modules/Core/resources/js/MasterLayout.vue`, `httpClient.ts`, `components/AppButton.vue`.
- Feature module: `Modules/Auth/resources/js/pages/Login.vue` using `MasterLayout` and `httpClient` from Core.

**Anti-examples (forbidden):**

- `Modules/Auth/resources/js/httpClient.ts` used by `Modules/Crawler` (shared client belongs in Core).
- `Modules/Crawler/resources/js/MasterLayout.vue` duplicated from Core and used by multiple features (use Core’s layout).
- Shared component that is used by multiple features but lives in `Modules/Auth` (move to Core).

**Enforcement:** Code review; convention that any “shared” or “common” FE asset used by 2+ features must live in Core.  
**References:** [01-module-boundaries](01-module-boundaries-and-dependencies.md), [06-code-review-checklist](06-code-review-checklist.md).

---

### FE-ARCH-006: Composition API and script setup only

**Rule:** Vue components MUST use Composition API with `<script setup lang="ts">` only. No Options API or `setup()` function returning an object in component files.

**Rationale:** Consistency and type inference; aligns with project stack.

**Allowed:**

- `<script setup lang="ts">` with `ref`, `computed`, `onMounted`, etc.

**Anti-examples (forbidden):**

- `<script lang="ts"> export default { setup() { ... } } </script>`.
- Options API with `data()`, `methods`, etc.

**Enforcement:** Code review; lint rule if available.  
**References:** [06-code-review-checklist](06-code-review-checklist.md).

---

## Summary Table

| Layer      | Role                  | Allowed                                     | Forbidden                        |
| ---------- | --------------------- | ------------------------------------------- | -------------------------------- |
| Page       | Orchestration, layout | Composables, Services, layout               | Business logic, raw HTTP         |
| Component  | Presentational        | Props, emits, local UI state                | API calls, global state          |
| Composable | UI logic/state        | State, Services                             | Markup, raw fetch                |
| Service    | API client wrapper    | HTTP calls, return data                     | UI state, business validation    |
| Shared     | Only in Core          | MasterLayout, httpClient, shared components | Shared assets in feature modules |

---

## Enforcement

- **PR:** Checklist in [06-code-review-checklist](06-code-review-checklist.md).
- **CI:** Optional: lint for script setup and import paths (no shared assets outside Core).
- **References:** [01-module-boundaries](01-module-boundaries-and-dependencies.md), [docs/reference/02-module-map](../reference/02-module-map.md).
