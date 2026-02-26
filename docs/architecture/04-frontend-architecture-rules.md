# 04 - Frontend Architecture Rules

## FE Layering
Rule `04-FE-001`:
Inertia Pages are orchestration/layout only; Components are presentational; Composables hold UI logic/state/fetch glue; FE Services are API/action clients only.

Rationale:
Clear FE layering prevents business logic leakage into views.

Allowed:
```ts
export function useUserList(service: UserService) { /* state + actions */ }
```

Forbidden:
```vue
<script setup lang="ts">
// page-level business logic and API orchestration here
</script>
```

Verification:
- Page files avoid direct API calls and heavy branching.

## Core FE Ownership
Rule `04-FE-002`:
Core FE owns `MasterLayout`, base/shared components, shared composables, and HTTP wrapper.

Rationale:
Shared UX/system behavior must be single-source.

Allowed:
```text
Modules/Core/resources/js/layouts/MasterLayout.vue
Modules/Core/resources/js/services/httpClient.ts
```

Forbidden:
```text
Modules/Orders/resources/js/services/httpClient.ts
```

Verification:
- Single shared HTTP wrapper in Core only.

## Feature FE Ownership
Rule `04-FE-003`:
Feature modules own only pages/components/services/types relevant to that module domain.

Rationale:
Prevents accidental shared coupling and random dumping.

Allowed:
```text
Modules/Orders/resources/js/pages/OrderListPage.vue
```

Forbidden:
```text
Modules/Orders/resources/js/components/GlobalButton.vue
```

Verification:
- Shared/generic components are only in Core.

## FE Directory Cleanliness
Rule `04-FE-004`:
No random folders/files under `resources/js`; allowed top-level dirs are `pages`, `components`, `composables`, `services`, `types`.

Rationale:
Predictable layout accelerates onboarding and review.

Allowed:
```text
resources/js/components/
resources/js/services/
```

Forbidden:
```text
resources/js/new_temp/
```

Verification:
- Directory checks match allowed list.

## UI Library Standard
Rule `04-FE-005`:
Use PrimeVue (latest stable), PrimeIcons, and Font Awesome only.

Rationale:
Unified UI primitives and iconography reduce inconsistency.

Allowed:
```ts
import Button from 'primevue/button';
import 'primeicons/primeicons.css';
```

Forbidden:
```ts
import { Button } from 'another-ui-library';
```

Verification:
- No additional UI framework imports.

## Base Component Reuse
Rule `04-FE-006`:
Reuse base components through props/slots/composables; avoid inheritance chains and copy-paste variants.

Rationale:
Composition scales better than pseudo-inheritance in Vue.

Allowed:
```vue
<BaseCard><template #title>Orders</template></BaseCard>
```

Forbidden:
```vue
<!-- BaseCardExtendedProMax -->
```

Verification:
- Generic behavior in `Core/components/base`.

## FE Hardcode Ban
Rule `04-FE-007`:
Domain literals in mapping/filter/status logic are forbidden; use enums/constants/types.

Rationale:
Prevents UI-domain drift and typo-prone conditions.

Allowed:
```ts
if (order.status === OrderStatus.Pending) {}
```

Forbidden:
```ts
if (order.status === 'pending') {}
```

Verification:
- Domain literals absent from FE logic unless exception-registered.

## FE Dependency Pinning
Rule `04-FE-008`:
PrimeVue and Font Awesome MUST be pinned by major version in `package.json`. Major upgrades require a migration note and exception registration before rollout.

Rationale:
Major-version changes can introduce breaking UI/API behavior and must be governed explicitly.

Allowed:
```json
{
  "primevue": "^4.0.0",
  "@fortawesome/fontawesome-free": "^6.0.0"
}
```

Forbidden:
```json
{
  "primevue": "latest",
  "@fortawesome/fontawesome-free": "latest"
}
```

Verification:
- `package.json` pins major versions for PrimeVue and Font Awesome.
- Major bump PR includes migration note and exception ID.
