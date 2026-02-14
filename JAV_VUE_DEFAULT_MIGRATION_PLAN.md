# JAV Vue-Default Migration Plan

## Objective
Make Vue/Inertia the default UI under `/jav/*`, move Blade UI to `/jav/blade/*` as archive, and keep current behavior stable during migration.

## Agreed Architecture
- `Modules/JAV/app/Http/Controllers/Api/*`: shared API endpoints for both Vue and Blade.
- `Modules/JAV/app/Http/Controllers/*Controller.php`: Vue/Inertia page controllers.
- `Modules/JAV/app/Http/Controllers/Blade/*Controller.php`: Blade page controllers.

## Safety Constraints
1. Do not break existing shared endpoint contracts (`method + URI + route name + response shape`) until explicit cleanup phase.
2. Do not swap URL prefixes and route names in one change.
3. Keep each phase as a separate commit/PR with verification evidence.
4. Prefer moving behavior by delegation first, then cleanup.

## Route Groups (target end state)
- Vue pages: `/jav/*`
- Blade pages: `/jav/blade/*`
- Shared API/actions: keep stable (mostly existing `jav.api.*` and selected `jav.*` actions)
- Legacy compatibility: temporary redirects from `/jav/vue/*` to `/jav/*`

---

## Phase 0 - Baseline and Inventory

### What
Capture the current route/usage baseline before refactoring.

### Why
You need a hard reference to detect accidental breakage after each phase.

### How
1. Export current route list.
2. Export Vue route-name usage in JS.
3. Detect mixed controllers (both Blade and Inertia/API behavior).
4. Commit these artifacts.

### Commands
```bash
mkdir -p storage/logs
php artisan route:list > storage/logs/jav-routes-before.txt
rg -n "route\('jav\.|route\('jav\.vue\.|route\('watchlist\.|route\('ratings\." Modules/JAV/resources/js > storage/logs/jav-vue-route-usage.txt
rg -n "Inertia::render|return view\(|JsonResponse|response\(\)->json" Modules/JAV/app/Http/Controllers > storage/logs/jav-controller-mix-scan.txt
```

### DoD (checkable)
- `storage/logs/jav-routes-before.txt` exists.
- `storage/logs/jav-vue-route-usage.txt` exists.
- `storage/logs/jav-controller-mix-scan.txt` exists.

---

## Phase 1 - Route Ownership Matrix

### What
Classify every `Modules/JAV/routes/web.php` route into one owner type:
- `VUE_PAGE`
- `BLADE_PAGE`
- `SHARED_ACTION` (non-page endpoint used by UI)
- `API` (JSON endpoint)

### Why
Prevents moving a route to Blade archive that Vue still relies on.

### How
1. Walk route groups in `Modules/JAV/routes/web.php`.
2. Record route name, URI, method, controller action, owner type.
3. Mark who consumes it (Vue/Blade/Both).
4. Add matrix section to this file and keep it updated.

### Phase 1 Outputs
- `storage/logs/route-list.json`
- `storage/logs/jav-route-ownership-matrix.csv`

### Current Classification Summary (from matrix)
- `API`: 17 routes
- `VUE_PAGE`: 24 routes
- `BLADE_PAGE`: 23 routes
- `SHARED_ACTION`: 29 routes

### DoD (checkable)
- No unclassified JAV web route remains.
- Every route has explicit owner and consumer.

### Check commands
```bash
wc -l storage/logs/jav-route-ownership-matrix.csv
awk -F, 'NR>1 {c[$5]++} END {for (k in c) print k, c[k]}' storage/logs/jav-route-ownership-matrix.csv | sort
```

---

## Phase 2 - Controller Split (No Public Route Changes)

### What
Move Blade rendering methods into `Controllers/Blade/*` while keeping routes and route names unchanged.

### Why
Separate responsibilities first with zero user-visible change.

### How
1. Create Blade controllers mirroring current feature areas.
2. Move Blade `view(...)` actions from mixed controllers into new Blade controllers.
3. Keep Inertia actions in root controllers.
4. Keep shared API/actions in `Controllers/Api/*` or current shared action controllers.
5. If logic is duplicated, extract shared service methods.

### File skeleton
- `Modules/JAV/app/Http/Controllers/Blade/DashboardController.php`
- `Modules/JAV/app/Http/Controllers/Blade/MovieController.php`
- `Modules/JAV/app/Http/Controllers/Blade/ActorController.php`
- `Modules/JAV/app/Http/Controllers/Blade/TagController.php`
- `Modules/JAV/app/Http/Controllers/Blade/LibraryController.php`
- `Modules/JAV/app/Http/Controllers/Blade/PreferenceController.php`
- `Modules/JAV/app/Http/Controllers/Blade/RatingController.php`
- `Modules/JAV/app/Http/Controllers/Blade/Admin/AnalyticsController.php`
- `Modules/JAV/app/Http/Controllers/Blade/Admin/SyncController.php`
- `Modules/JAV/app/Http/Controllers/Blade/Admin/SearchQualityController.php`

### DoD (checkable)
- Root non-Api controllers no longer contain Blade `view(...)` methods (except temporary delegators if intentionally kept).
- Route table diff has no public changes.

### Commands
```bash
php artisan route:list > storage/logs/jav-routes-after-phase2.txt
diff -u storage/logs/jav-routes-before.txt storage/logs/jav-routes-after-phase2.txt
rg -n "return view\(" Modules/JAV/app/Http/Controllers
```

---

## Phase 3 - Move Blade Pages to `/jav/blade/*`

### What
Relocate Blade page routes from `/jav/*` to `/jav/blade/*` and rename to `jav.blade.*`.

### Why
Free `/jav/*` for canonical Vue pages and keep Blade as archive access only.

### How
1. Add Blade archived route group:
   - `prefix('jav/blade')->name('jav.blade.')`
2. Move Blade page GET routes into this group.
3. Keep `SHARED_ACTION` routes unchanged for now.
4. Update Blade view links to `jav.blade.*` where needed.

### DoD (checkable)
- Blade pages load under `/jav/blade/*`.
- No Blade page GET route remains on canonical `/jav/<page>` paths.
- Shared action/API routes still pass smoke checks.

### Commands
```bash
php artisan route:list | rg "jav\.blade|/jav/blade"
php artisan route:list | rg "jav\.request|jav\.status|jav\.admin\.sync-progress\.data|jav\.admin\.search-quality\.preview|jav\.admin\.provider-sync\.dispatch"
```

---

## Phase 4 - Promote Vue URLs to `/jav/*`

### What
Change Vue route prefix from `/jav/vue/*` to `/jav/*`.

### Why
Make Vue the default user experience and canonical path.

### How
1. Change Vue route group `prefix('jav/vue')` -> `prefix('jav')`.
2. Temporarily keep route names as `jav.vue.*` to reduce JS churn.
3. Add legacy redirects from `/jav/vue/*` to corresponding `/jav/*`.
4. Ensure no URI collisions with Blade archived or shared action routes.

### DoD (checkable)
- Vue pages accessible at `/jav/*`.
- `/jav/vue/*` resolves via redirects.
- Route registration has no duplicate conflict.

### Commands
```bash
php artisan route:list | rg "/jav/vue|/jav/dashboard|/jav/movies|/jav/actors|/jav/tags"
php artisan route:list | rg "jav\.vue\."
```

---

## Phase 5 - Frontend Refactor for Canonical URLs

### What
Remove frontend assumptions tied to `/jav/vue/*` and optionally normalize route names.

### Why
Avoid brittle path checks and complete migration to canonical behavior.

### How
1. Replace hardcoded path checks in Vue layout/components.
2. Prefer route-name driven active states.
3. Keep compatibility aliases during rollout window.
4. Optional second step: rename `jav.vue.*` page names to `jav.*` with alias fallback.

### High-priority files
- `Modules/JAV/resources/js/Layouts/Partials/Navbar.vue`
- `Modules/JAV/resources/js/Layouts/Partials/Sidebar.vue`
- All pages referencing `route('jav.vue.*')`

### DoD (checkable)
- No hardcoded `/jav/vue/` checks remain.
- Sidebar/navbar active states work on canonical URLs.
- Frontend build passes.

### Commands
```bash
rg -n "/jav/vue/" Modules/JAV/resources/js
npm run build
```

---

## Phase 6 - Compatibility Cleanup

### What
Remove temporary aliases/redirects after stability window.

### Why
Reduce maintenance overhead and avoid permanent dual-route complexity.

### How
1. Remove legacy `/jav/vue/*` redirects when traffic confirms no dependency.
2. Remove temporary route-name aliases if introduced.
3. Re-run smoke tests + route snapshot.

### DoD (checkable)
- No dependency on legacy `/jav/vue/*` routes.
- Route table contains only target architecture.
- Smoke tests pass.

### Commands
```bash
php artisan route:clear
php artisan route:list > storage/logs/jav-routes-final.txt
```

---

## Route Ownership Matrix

### Vue page routes (target canonical `/jav/*`)
- `jav.vue.dashboard`
- `jav.vue.movies.show`
- `jav.vue.actors`
- `jav.vue.actors.bio`
- `jav.vue.tags`
- `jav.vue.history`
- `jav.vue.favorites`
- `jav.vue.watchlist`
- `jav.vue.recommendations`
- `jav.vue.ratings`
- `jav.vue.ratings.show`
- `jav.vue.notifications`
- `jav.vue.preferences`
- `jav.vue.admin.sync`
- `jav.vue.admin.analytics`
- `jav.vue.admin.sync-progress`
- `jav.vue.admin.search-quality`
- `jav.vue.admin.provider-sync`
- `jav.vue.javs.index`
- `jav.vue.javs.create`
- `jav.vue.javs.show`
- `jav.vue.javs.edit`
- `jav.vue.login`
- `jav.vue.register`

### Shared actions (keep stable through Phase 4)
- `jav.request`
- `jav.status`
- `jav.admin.sync-progress.data`
- `jav.admin.search-quality.preview`
- `jav.admin.search-quality.publish`
- `jav.admin.provider-sync.dispatch`
- `jav.presets.save`
- `jav.presets.delete`
- `jav.preferences.save`
- `jav.movies.download`
- `jav.movies.view`
- `watchlist.*`
- `ratings.*`

### API routes
- `jav.api.*`

### Blade page routes (move to `jav.blade.*`)
- `jav.dashboard`
- `jav.movies.show`
- `jav.actors`
- `jav.actors.bio`
- `jav.tags`
- `jav.history`
- `jav.favorites`
- `jav.recommendations`
- `jav.preferences`
- `jav.notifications`
- `jav.admin.analytics`
- `jav.admin.sync-progress`
- `jav.admin.search-quality.index`
- `jav.admin.provider-sync.index`

Detailed per-route matrix (method, uri, route name, action, owner, consumer):
- `storage/logs/jav-route-ownership-matrix.csv`

---

## Handoff Log (P0 + P1)

### What Has Been Done
1. Phase 0 baseline was executed.
2. Phase 1 route ownership classification was executed.
3. This plan was updated with phase outputs and verification commands.

### What We Have (Artifacts)
- `storage/logs/jav-routes-before.txt`: baseline route snapshot before migration edits.
- `storage/logs/jav-vue-route-usage.txt`: route-name usage extracted from Vue code.
- `storage/logs/jav-controller-mix-scan.txt`: mixed-controller scan (`view`, `Inertia`, JSON markers).
- `storage/logs/jav-blade-ajax-usage.txt`: Blade route + AJAX/form endpoint usage baseline.
- `storage/logs/route-list.json`: structured output from `php artisan route:list --json`.
- `storage/logs/jav-route-ownership-matrix.csv`: per-route matrix with owner + consumer fields.

### What We Know (Confirmed from P0/P1)
1. Vue depends on both `jav.vue.*` page routes and multiple `jav.*` shared-action endpoints.
2. Blade also uses AJAX/form endpoints that overlap with Vue flows.
3. Shared endpoints with `consumer=Both` (migration-critical) include:
   - `jav.admin.provider-sync.dispatch`
   - `jav.admin.search-quality.preview`
   - `jav.admin.search-quality.publish`
   - `jav.admin.sync-progress.data`
   - `jav.preferences.save`
   - `jav.presets.save`
   - `jav.presets.delete`
   - `jav.movies.download`
4. Controllers are currently mixed; Phase 2 must split Blade page rendering without changing route contracts.
5. Current route ownership totals:
   - `API`: 17
   - `VUE_PAGE`: 24
   - `BLADE_PAGE`: 23
   - `SHARED_ACTION`: 29

### Tracking Guidance For Follow-Up
1. Use `storage/logs/jav-route-ownership-matrix.csv` as the route movement source of truth.
2. Before changing routes/controllers, compare behavior against `storage/logs/jav-routes-before.txt`.
3. For each moved/renamed route, verify consumers via:
   - `storage/logs/jav-vue-route-usage.txt`
   - `storage/logs/jav-blade-ajax-usage.txt`
4. Keep shared endpoints stable until compatibility aliases/redirects are implemented.

---

## Handoff Update (P2)

### What Has Been Done
1. Added Blade wrapper controllers under `Modules/JAV/app/Http/Controllers/Blade/*`.
2. Rewired Blade page GET routes in `Modules/JAV/routes/web.php` to Blade wrappers.
3. Kept shared write/API routes on existing controllers to preserve contracts.

### What We Have (P2 Artifacts)
- `storage/logs/route-list-after-phase2.json`
- `storage/logs/jav-routes-after-phase2.txt`
- `storage/logs/routes-before-contract.tsv`
- `storage/logs/routes-after-contract.tsv`
- `storage/logs/routes-contract-phase2.diff` (empty; 0 lines)
- `storage/logs/jav-get-routes-after-phase2.tsv`

### What We Know (Confirmed from P2)
1. Public route contract is unchanged (`method + uri + name + middleware` diff is empty).
2. Blade page GET routes now resolve to `Modules\\JAV\\Http\\Controllers\\Blade\\...`.
3. Vue routes remain under `/jav/vue/*` unchanged in this phase.
4. Shared endpoints used by Vue/Blade remained on original action/API controllers.

### Validation Notes
1. `php artisan route:list` succeeds after refactor.
2. PHP lint passes for `Modules/JAV/routes/web.php` and all `Controllers/Blade/*`.
3. Focused feature suite for rewired areas passes:
   - `35 passed` (`storage/logs/p2-focused-tests.log`, exit `storage/logs/p2-focused-tests.exit=0`).
4. Auth feature suite has one existing failure:
   - `Modules/JAV/tests/Feature/AuthTest.php:58` (`storage/logs/p2-auth-test.log`, exit `storage/logs/p2-auth-test.exit=1`).
   - Root cause (deferred): test posts `username`, while `Modules/JAV/app/Http/Controllers/Auth/LoginController.php` expects `login`.

---

## Recommended PR Sequence
1. Phase 0 + Phase 1 (inventory + matrix only).
2. Phase 2 (controller split, no route changes).
3. Phase 3 (Blade archived URLs).
4. Phase 4 (Vue canonical URLs + redirects).
5. Phase 5 (frontend cleanup + optional route name normalization).
6. Phase 6 (remove compatibility routes).

## Rollback Strategy
- One phase per PR.
- If verification fails, revert that PR only.
- Never combine controller split + prefix switch + route-name rename in one PR.

---

## Handoff Update (P7)

### What Has Been Done
1. Restored temporary legacy GET route-name alias `jav.notifications` to point to canonical Vue notifications page (`/jav/notifications`).
2. Added login payload compatibility so guest auth accepts both `login` and `username`.
3. Re-ran targeted feature tests for auth and notifications.

### What We Have (P7 Artifacts)
- `storage/logs/p7-notifications-routes.txt`
- `storage/logs/p7-auth-test.log`
- `storage/logs/p7-auth-test.exit`
- `storage/logs/p7-notifications-test.log`
- `storage/logs/p7-notifications-test.exit`
- `storage/logs/route-list-after-phase7.json`

### What We Know (Confirmed from P7)
1. Auth feature suite now passes (`6 passed`).
2. Notifications feature suite passes (`3 passed`).
3. Legacy route-name dependency risk is mitigated by alias restoration.

## Handoff Update (P8)

### What Has Been Done
1. Moved Vue user/web controllers from `Modules/JAV/app/Http/Controllers/*` into `Modules/JAV/app/Http/Controllers/Users/*`.
2. Moved Vue guest auth controllers from `Modules/JAV/app/Http/Controllers/Auth/*` into `Modules/JAV/app/Http/Controllers/Guest/Auth/*`.
3. Moved Vue user API controllers from `Modules/JAV/app/Http/Controllers/Api/*` into `Modules/JAV/app/Http/Controllers/Users/Api/*`.
4. Updated `Modules/JAV/routes/web.php` and `Modules/JAV/routes/api.php` imports to new namespaces.
5. Updated Blade wrapper base-controller imports to follow the new `Users/*` and `Guest/Auth/*` namespaces.

### What We Have (P8 Artifacts)
- `storage/logs/route-list-before-phase8.json`
- `storage/logs/route-list-after-phase8.json`
- `storage/logs/p8-contract-before.tsv`
- `storage/logs/p8-contract-after.tsv`
- `storage/logs/p8-contract.diff`
- `storage/logs/jav-routes-after-phase8.txt`
- `storage/logs/p8-vue-routes.txt`
- `storage/logs/p8-focused-tests.log`
- `storage/logs/p8-focused-tests.exit`

### What We Know (Confirmed from P8)
1. Route contract remained stable (diff is empty for `method + uri + name + middleware`).
2. Vue page routes are now served from `Users/*` and guest Vue auth routes from `Guest/Auth/*`.
3. Focused migration regression suite passed (`45 passed`, `120 assertions`).

## Handoff Update (P9)

### What Has Been Done
1. Added `Modules/JAV/app/Http/Controllers/Admin/Api/SyncController.php`.
2. Added `Modules/JAV/app/Http/Controllers/Admin/Api/SearchQualityController.php`.
3. Moved admin JSON/action endpoints (`request`, `status`, `syncProgressData`, `dispatch`, `preview`, `publish`) to `Admin/Api/*`.
4. Reduced `Modules/JAV/app/Http/Controllers/Admin/SyncController.php` and `Modules/JAV/app/Http/Controllers/Admin/SearchQualityController.php` to view-render responsibilities only.
5. Updated `Modules/JAV/routes/web.php` admin action routes to use `Admin/Api/*` controllers without changing route names/URIs/middleware.
6. Added dedicated request classes for search-quality admin API validation.

### What We Have (P9 Artifacts)
- `storage/logs/route-list-before-phase9.json`
- `storage/logs/route-list-after-phase9.json`
- `storage/logs/p9-contract-before.tsv`
- `storage/logs/p9-contract-after.tsv`
- `storage/logs/p9-contract.diff`
- `storage/logs/p9-admin-api-routes.tsv`
- `storage/logs/jav-routes-after-phase9.txt`
- `storage/logs/p9-focused-tests.log`
- `storage/logs/p9-focused-tests.exit`

### What We Know (Confirmed from P9)
1. Route contract remained stable (empty diff for `method + uri + name + middleware`).
2. Admin action routes now resolve to `Modules\\JAV\\Http\\Controllers\\Admin\\Api\\...`.
3. Focused regression suite passed (`16 passed`, `48 assertions`).

## Handoff Update (P10)

### What Has Been Done
1. Added `Modules/JAV/app/Http/Controllers/Users/Api/MovieController.php`.
2. Rewired `jav.toggle-like` route to `Modules\\JAV\\Http\\Controllers\\Users\\Api\\LibraryController@toggleLike`.
3. Rewired `jav.movies.view` route to `Modules\\JAV\\Http\\Controllers\\Users\\Api\\MovieController@view`.
4. Removed JSON-only methods from page controllers:
   - `Users/LibraryController::toggleLike`
   - `Users/MovieController::view`
5. Kept redirect-capable action routes unchanged to avoid breaking Blade forms.

### What We Have (P10 Artifacts)
- `storage/logs/route-list-before-phase10.json`
- `storage/logs/route-list-after-phase10.json`
- `storage/logs/p10-contract-before.tsv`
- `storage/logs/p10-contract-after.tsv`
- `storage/logs/p10-contract.diff`
- `storage/logs/p10-user-api-moved-routes.tsv`
- `storage/logs/jav-routes-after-phase10.txt`
- `storage/logs/p10-focused-tests.log`
- `storage/logs/p10-focused-tests.exit`

### What We Know (Confirmed from P10)
1. Route contract remained stable (empty diff for `method + uri + name + middleware`).
2. JSON-only user actions above are now served from `Users/Api/*`.
3. Focused regression suite passed (`38 passed`, `91 assertions`).

## Handoff Update (P11)

### What Has Been Done
1. Refactored mixed controllers to delegate JSON branches to `Users/Api/*` handlers:
   - `Modules/JAV/app/Http/Controllers/Users/WatchlistController.php`
   - `Modules/JAV/app/Http/Controllers/Users/RatingController.php`
   - `Modules/JAV/app/Http/Controllers/Users/NotificationController.php`
2. Kept redirect/non-JSON branches in place for Blade form compatibility.
3. Kept all route definitions unchanged (no route rewiring in this phase).

### What We Have (P11 Artifacts)
- `storage/logs/route-list-after-phase11.json`
- `storage/logs/p11-contract-before.tsv`
- `storage/logs/p11-contract-after.tsv`
- `storage/logs/p11-contract.diff`
- `storage/logs/jav-routes-after-phase11.txt`
- `storage/logs/p11-mixed-routes.tsv`
- `storage/logs/p11-focused-tests.log`
- `storage/logs/p11-focused-tests.exit`

### What We Know (Confirmed from P11)
1. Route contract remained stable (empty diff for `method + uri + name + middleware`).
2. Mixed routes are still bound to `Users/*` controllers, but JSON paths now use shared `Users/Api/*` logic internally.
3. Focused regression suite passed (`38 passed`, `91 assertions`).
