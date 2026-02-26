# Skill Catalog

## 1) `be-feature-implementation`
- Purpose: Deliver backend feature changes in one module following strict Laravel layering.
- Inputs: approved feature spec, module name, route requirements, data changes.
- Outputs (files): module BE files under `Modules/<Feature>/app`, `routes`, `tests`, and required doc deltas.
- Constraints: thin controllers, strict layering, 1 model-1 repository, no hardcode, scoped changes only.
- DoD: `09-DOD-002` BE items pass with evidence.
- Pitfalls: business logic in controller, internal service mocking in feature tests.

### Delta/Clarification
- Enforce PHPUnit-only for backend tests.
- Enforce grouped route names: `render.*`, `action.*`, `api.v1.*`.

## 2) `fe-feature-implementation`
- Purpose: Deliver feature FE pages/components/composables/services aligned with Inertia layering.
- Inputs: approved feature spec, FE flow, API contract, module FE scope.
- Outputs (files): `resources/js/{pages,components,composables,services,types}` + FE tests.
- Constraints: page orchestration only, no business logic in page, Core layout/base components reuse.
- DoD: FE unit/component tests and critical E2E pass.
- Pitfalls: page-level API logic, random folders, duplicate base components.

### Delta/Clarification (`Inertia Page + API Wiring`)
- Page renders minimal props; fetching and state glue stay in composables/services.
- FE service layer is the only caller for `api.v1.*`/`action.*` endpoints.

## 3) `api-contract-design`
- Purpose: Define REST API contracts and schema-consistent responses.
- Inputs: resource operations, auth rules, filter/sort/pagination requirements.
- Outputs (files): `routes/api_v1.php`, FormRequests, Resources/DTO contracts, API tests.
- Constraints: `/api/v1`, RESTful resources, unified success/error schema.
- DoD: status/schema tests pass for happy/unhappy/security/edge cases.
- Pitfalls: verb-in-path routes, inconsistent envelopes.

### Delta/Clarification (`API Contract & Resource Writer`)
- Use `JsonResource` for API response projection.
- Enforce error envelope with standard `error.code/message/details/trace_id`.
- Auth non-resource actions are exception-registered only.

## 4) `integration-adapter-implementation`
- Purpose: Integrate external systems behind internal ports.
- Inputs: external API contract, auth/retry/idempotency constraints.
- Outputs (files): Core interface, adapter implementation, boundary DTOs, tests.
- Constraints: domain services depend on interface only.
- DoD: mapping and failure paths are tested and stable.
- Pitfalls: leaking vendor client into domain service.

### Delta/Clarification (`Integration Adapter Builder`)
- Mandatory `jooservices/client` usage via adapter/interface.
- Integration request/response DTOs use `jooservices/dto` only for boundaries.

## 5) `qa-test-design`
- Purpose: Build test strategy and required scenario matrix per feature.
- Inputs: feature spec, risk analysis, critical flows.
- Outputs (files): test matrix and implementation checklist references.
- Constraints: happy/unhappy/weird/security/edge required; no placeholders.
- DoD: each acceptance criterion maps to at least one test.
- Pitfalls: happy path only, missing FormRequest test coverage.

### Delta/Clarification
- Backend: PHPUnit only.
- Frontend: Vitest + Vue Test Utils for unit/component, Playwright for E2E.

## 6) `production-readiness-review`
- Purpose: Verify release readiness with objective evidence.
- Inputs: diff, tests, quality outputs, exceptions.
- Outputs (files): readiness report and remediation list.
- Constraints: no assumption-based PASS.
- DoD: checklist PASS/FAIL with references.
- Pitfalls: subjective approvals, ignored exception expiry.

### Delta/Clarification (`Feature DoD Verifier`)
- Explicitly reject “done khong” if any DoD item lacks evidence.
- Verify route grouping/naming, toolchain pass, observability for mutations.

## 7) `route-group-controller-scaffold`
- Purpose: Scaffold grouped routes and controller method signatures per module.
- Inputs: module name, route group name, auth/resource route list.
- Outputs (files): `Modules/<Feature>/routes/web.php`, `routes/api_v1.php`, `Controllers/{Group}Controller.php`, `Controllers/Api{Group}Controller.php`.
- Constraints: `web.php` contains both `render.*` and `action.*`; `api_v1.php` uses `api.v1.*`; AuthController uses `renderX/actionX`; ApiAuthController uses `login/logout/...`.
- DoD: all route names/prefixes/method signatures follow standards.
- Pitfalls: split web routes across files, inconsistent naming prefixes.

## 8) `formrequest-author-authorization-wiring`
- Purpose: Build FormRequest validation + authorization flow and tests.
- Inputs: endpoint contract, policy/gate requirement, field normalization rules.
- Outputs (files): `Http/Requests/*Request.php`, policy/gate wiring, FormRequest tests.
- Constraints: implement `authorize()`, `rules()`, `prepareForValidation()` when needed; no controller inline validation for same scope.
- DoD: FormRequest tests cover allow/deny + validation unhappy paths.
- Pitfalls: empty `authorize()` default true without policy intent.

## 9) `service-orchestrator-builder`
- Purpose: Implement service-level orchestration with transaction boundaries.
- Inputs: use-case steps, repositories used, domain event triggers.
- Outputs (files): `Services/*Service.php`, optional events dispatch points, service tests.
- Constraints: transactions only in service layer; early returns; avoid deep nesting; no DB facade/query in controller/service except transaction boundary; dispatch events for happened facts.
- DoD: orchestration flow + rollback/edge cases tested.
- Pitfalls: business logic in listeners, nested conditional pyramids.

## 10) `repository-query-designer`
- Purpose: Implement efficient repository queries with paging/filter/sort and eager loading.
- Inputs: model, filter/sort contract, include relations.
- Outputs (files): `Repositories/*Repository.php`, repository unit/integration tests.
- Constraints: query logic only in repository; no DB query in service/controller; support standardized pagination/filter/sort.
- DoD: query behavior and N+1 prevention verified.
- Pitfalls: leaking query builder into service layer.

## 11) `redis-keyspace-idempotency-designer`
- Purpose: Design Redis keyspace patterns and idempotency dedupe flow.
- Inputs: module/entity/action, TTL policy, idempotency window.
- Outputs (files): constants/enums for key patterns, idempotency service/helper, tests.
- Constraints: key format `{module}:{entity}:{purpose}:{id}`; TTL mandatory for cache/idempotency; dedupe for non-idempotent mutations.
- DoD: duplicate requests safely return same outcome and no double side effects.
- Pitfalls: no TTL, opaque key names, dedupe check after side effects.

## 12) `dto-anti-abuse-checker`
- Purpose: Enforce DTO budget and boundary-only usage.
- Inputs: feature scope, proposed DTO list, boundary map.
- Outputs (files): DTO decision report in feature spec/PR notes; only required DTO files.
- Constraints: budget 2-4 DTOs per feature by default; DTO must provide contract/normalization/mapping/invariant value; internal flow prefers VO/typed params.
- DoD: each DTO has explicit value justification and boundary location.
- Pitfalls: model-clone DTOs and method-to-method DTO chaining.

## 13) `quality-tool-fixer`
- Purpose: Resolve quality failures in strict order with scoped changes.
- Inputs: Pint/PHPCS/PHPStan/PHPMD outputs, feature scope.
- Outputs (files): only scoped code/config fixes and updated evidence notes.
- Constraints: apply Pint first; then PHPCS/PHPStan/PHPMD; no unrelated cleanup/refactor.
- DoD: all required tools pass for scoped files; no new exceptions unless approved.
- Pitfalls: fixing PHPCS first and fighting Pint; broad refactors hidden as lint fixes.

## 14) `primevue-base-component-builder`
- Purpose: Build reusable Core FE base components with tests.
- Inputs: component contract (props/slots/events), design tokens, usage sites.
- Outputs (files): `Modules/Core/resources/js/components/base/*.vue`, related composables/types, Vitest component tests.
- Constraints: PrimeVue/PrimeIcons/FontAwesome only; reusable via props/slots; no feature-specific domain logic.
- DoD: component reused in >=2 call sites or justified as foundational base.
- Pitfalls: feature-specific behavior baked into Core base components.

## 15) `fe-api-client-generator`
- Purpose: Generate typed FE service clients for `api.v1.*` and `action.*` endpoints.
- Inputs: route contract list, request/response types, error schema.
- Outputs (files): `resources/js/services/*Client.ts`, `types/*.ts`, tests for mapping/error handling.
- Constraints: only FE services call HTTP wrapper; typed responses; unified error schema mapping.
- DoD: client methods match backend route names/contracts and handle error envelope.
- Pitfalls: untyped `any` payloads, API calls directly in pages/components.

## 16) `fe-component-test-generator`
- Purpose: Generate FE unit/component tests.
- Inputs: target component list, behavior assertions, mock dependencies.
- Outputs (files): `tests/Frontend/unit/*.spec.ts`.
- Constraints: Vitest + Vue Test Utils only; behavior assertions (no placeholders).
- DoD: assertions cover render states, user interaction, emitted events, error states.
- Pitfalls: snapshot-only tests without behavior checks.

## 17) `fe-e2e-flow-generator`
- Purpose: Generate Playwright E2E tests for critical user journeys.
- Inputs: critical flow definitions, test data setup, auth needs.
- Outputs (files): `tests/Frontend/e2e/*.spec.ts`, optional fixtures.
- Constraints: Playwright only; cover critical flows end-to-end with deterministic setup.
- DoD: critical journeys pass in clean environment and assert user-visible outcomes.
- Pitfalls: brittle selectors and environment-coupled assertions.
