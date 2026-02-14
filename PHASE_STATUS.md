# Phase Status - JAV Vue Default Migration

## Summary

| Phase | Status | Scope | Result |
|---|---|---|---|
| P0 | Done | Baseline snapshot/inventory | Completed with artifacts |
| P1 | Done | Route ownership classification | Completed with matrix |
| P2 | Done | Controller split (no route changes) | Completed with wrappers + route rewiring |
| P3 | Done | Move Blade pages to `/jav/blade/*` | Completed with route/view/test updates |
| P4 | Done | Promote Vue to `/jav/*` + redirects | Completed with canonical routes + legacy redirects |
| P5 | Done | Frontend cleanup for canonical URLs | Completed with legacy-path cleanup + regressions green |
| P6 | Done | Remove compatibility routes | Completed with legacy redirect removal + final verification |
| P7 | Done | Compatibility stabilization | Restored legacy notifications route-name alias + login payload fallback |
| P8 | Done | Vue controller domain refactor | Moved Vue controllers to `Admin/`, `Users/`, `Guest/` namespaces with stable route contracts |
| P9 | Done | Admin API split | Moved admin JSON/action endpoints to `Controllers/Admin/Api/*` with stable route contracts |
| P10 | Done | User API cleanup (safe slice) | Moved JSON-only user actions to `Controllers/Users/Api/*` with stable route contracts |
| P11 | Done | Mixed controller split (internal delegation) | Delegated JSON branches to `Users/Api/*` while preserving Blade redirect behavior |

## Completed Work

### P0 - Baseline and Inventory
- Exported route snapshot before migration.
- Exported Vue route usage references.
- Scanned controller files for mixed responsibilities.
- Added Blade AJAX/form usage baseline for safe route updates.

Artifacts:
- `storage/logs/jav-routes-before.txt`
- `storage/logs/jav-vue-route-usage.txt`
- `storage/logs/jav-controller-mix-scan.txt`
- `storage/logs/jav-blade-ajax-usage.txt`

### P1 - Route Ownership Matrix
- Generated structured route list from Laravel.
- Classified JAV-related routes into:
  - `API`
  - `VUE_PAGE`
  - `BLADE_PAGE`
  - `SHARED_ACTION`
- Marked consumer ownership where detectable:
  - `Vue`
  - `Blade`
  - `Both`
  - `None`

Artifacts:
- `storage/logs/route-list.json`
- `storage/logs/jav-route-ownership-matrix.csv`

### P2 - Controller Split (No Public Route Changes)
- Added Blade wrapper controllers under `Modules/JAV/app/Http/Controllers/Blade/*`.
- Rewired Blade page GET routes to Blade wrapper controllers.
- Kept shared actions/API endpoints on existing non-Blade controllers.
- Verified route contract stability (`method + uri + name + middleware`) against baseline.

Artifacts:
- `storage/logs/route-list-after-phase2.json`
- `storage/logs/jav-routes-after-phase2.txt`
- `storage/logs/routes-before-contract.tsv`
- `storage/logs/routes-after-contract.tsv`
- `storage/logs/routes-contract-phase2.diff` (expected empty)
- `storage/logs/jav-get-routes-after-phase2.tsv`
- `storage/logs/p2-focused-tests.log`
- `storage/logs/p2-focused-tests.exit`
- `storage/logs/p2-auth-test.log`
- `storage/logs/p2-auth-test.exit`

### P3 - Move Blade Pages To `/jav/blade/*`
- Moved Blade page GET routes to `/jav/blade/*` with names `jav.blade.*`.
- Removed Blade page occupancy on old `/jav/*` paths.
- Updated Blade view route links/navigation and affected feature tests.
- Kept shared/API/action endpoints stable.

Artifacts:
- `storage/logs/jav-routes-after-phase3.txt`
- `storage/logs/route-list-after-phase3.json`
- `storage/logs/p3-shared-before.tsv`
- `storage/logs/p3-shared-after.tsv`
- `storage/logs/p3-shared-contract.diff`
- `storage/logs/jav-blade-ajax-usage-phase3.txt`
- `storage/logs/p3-focused-tests.log`
- `storage/logs/p3-focused-tests.exit`
- `storage/logs/p3-auth-test.log`
- `storage/logs/p3-auth-test.exit`

### P4 - Promote Vue To `/jav/*`
- Promoted Vue route group from `/jav/vue/*` to canonical `/jav/*` while keeping `jav.vue.*` route names.
- Added compatibility redirect routes for legacy `/jav/vue/*` URLs.
- Updated Vue navbar path checks to support canonical `/jav/*` and legacy `/jav/vue/*`.
- Kept shared/API/action contracts stable for tracked critical endpoints.
- Moved Vue guest pages to `/jav/login` and `/jav/register` with legacy redirects from `/jav/vue/login|register`.

Artifacts:
- `storage/logs/jav-routes-after-phase4.txt`
- `storage/logs/route-list-after-phase4.json`
- `storage/logs/p4-vue-routes.tsv`
- `storage/logs/p4-legacy-vue-routes.tsv`
- `storage/logs/p4-shared-before.tsv`
- `storage/logs/p4-shared-after.tsv`
- `storage/logs/p4-shared-contract.diff`
- `storage/logs/p4-focused-tests.log`
- `storage/logs/p4-focused-tests.exit`
- `storage/logs/p4-auth-test.log`
- `storage/logs/p4-auth-test.exit`

### P5 - Frontend Canonical Cleanup
- Removed remaining hardcoded `/jav/vue/*` path assumptions from Vue navbar route detection.
- Kept `jav.vue.*` route names unchanged for compatibility; canonical URLs remain `/jav/*`.
- Verified no `/jav/vue/` hardcoded path checks remain in `Modules/JAV/resources/js`.

Artifacts:
- `storage/logs/route-list-after-phase5.json`
- `storage/logs/p5-build.log`
- `storage/logs/p5-build.exit`
- `storage/logs/p5-focused-tests.log`
- `storage/logs/p5-focused-tests.exit`
- `storage/logs/p5-auth-test.log`
- `storage/logs/p5-auth-test.exit`

### P6 - Compatibility Cleanup
- Removed legacy `/jav/vue/*` compatibility redirect routes from `Modules/JAV/routes/web.php`.
- Removed legacy guest redirects `/jav/vue/login` and `/jav/vue/register`.
- Kept canonical Vue routes on `/jav/*` and Blade archive routes on `/jav/blade/*`.
- Kept deferred compatibility decision unchanged: no `jav.notifications` GET alias added.

Artifacts:
- `storage/logs/jav-routes-after-phase6.txt`
- `storage/logs/route-list-after-phase6.json`
- `storage/logs/p6-legacy-vue-routes.tsv`
- `storage/logs/p6-shared-before.tsv`
- `storage/logs/p6-shared-after.tsv`
- `storage/logs/p6-shared-contract.diff`
- `storage/logs/p6-build.log`
- `storage/logs/p6-build.exit`
- `storage/logs/p6-focused-tests.log`
- `storage/logs/p6-focused-tests.exit`
- `storage/logs/p6-auth-test.log`
- `storage/logs/p6-auth-test.exit`

### P7 - Compatibility Stabilization
- Restored legacy GET route name alias `jav.notifications` to canonical Vue notifications page.
- Added login payload compatibility in guest auth login controller to accept either `login` or `username`.
- Re-ran Auth and Notifications feature tests with testing DB.

Artifacts:
- `storage/logs/p7-notifications-routes.txt`
- `storage/logs/p7-auth-test.log`
- `storage/logs/p7-auth-test.exit`
- `storage/logs/p7-notifications-test.log`
- `storage/logs/p7-notifications-test.exit`
- `storage/logs/route-list-after-phase7.json`

### P8 - Vue Controller Domain Refactor (Move-Only)
- Moved Vue/web user controllers from `Controllers/*` to `Controllers/Users/*`.
- Moved Vue guest auth controllers from `Controllers/Auth/*` to `Controllers/Guest/Auth/*`.
- Moved Vue user API controllers from `Controllers/Api/*` to `Controllers/Users/Api/*`.
- Updated Blade wrapper base-controller imports to new `Users/*` and `Guest/Auth/*` namespaces.
- Updated `Modules/JAV/routes/web.php` and `Modules/JAV/routes/api.php` imports to new namespaces.
- Preserved route contract (`method + uri + name + middleware`) with zero diff.

Artifacts:
- `storage/logs/route-list-before-phase8.json`
- `storage/logs/route-list-after-phase8.json`
- `storage/logs/p8-contract-before.tsv`
- `storage/logs/p8-contract-after.tsv`
- `storage/logs/p8-contract.diff`
- `storage/logs/jav-routes-after-phase8.txt`
- `storage/logs/p8-vue-routes.txt`
- `storage/logs/p8-focused-tests.log`
- `storage/logs/p8-focused-tests.exit`

### P9 - Admin API Split (Move-Only)
- Added admin API controllers under `Modules/JAV/app/Http/Controllers/Admin/Api/*`.
- Moved admin JSON/action endpoints from page controllers to admin API controllers:
  - sync dispatch/status/request/progress-data
  - search quality preview/publish
- Kept admin view controllers (`Admin/SyncController`, `Admin/SearchQualityController`) as render-only.
- Preserved route contract (`method + uri + name + middleware`) with zero diff.

Artifacts:
- `storage/logs/route-list-before-phase9.json`
- `storage/logs/route-list-after-phase9.json`
- `storage/logs/p9-contract-before.tsv`
- `storage/logs/p9-contract-after.tsv`
- `storage/logs/p9-contract.diff`
- `storage/logs/p9-admin-api-routes.tsv`
- `storage/logs/jav-routes-after-phase9.txt`
- `storage/logs/p9-focused-tests.log`
- `storage/logs/p9-focused-tests.exit`

### P10 - User API Cleanup (Safe Slice)
- Added `Modules/JAV/app/Http/Controllers/Users/Api/MovieController.php`.
- Moved `jav.toggle-like` action to `Users/Api/LibraryController`.
- Moved `jav.movies.view` action to `Users/Api/MovieController`.
- Removed JSON-only methods from page controllers:
  - `Users/LibraryController::toggleLike`
  - `Users/MovieController::view`
- Kept redirect-capable mixed routes (watchlist/ratings/notifications) unchanged for Blade compatibility.

Artifacts:
- `storage/logs/route-list-before-phase10.json`
- `storage/logs/route-list-after-phase10.json`
- `storage/logs/p10-contract-before.tsv`
- `storage/logs/p10-contract-after.tsv`
- `storage/logs/p10-contract.diff`
- `storage/logs/p10-user-api-moved-routes.tsv`
- `storage/logs/jav-routes-after-phase10.txt`
- `storage/logs/p10-focused-tests.log`
- `storage/logs/p10-focused-tests.exit`

### P11 - Mixed Controller Split (Internal Delegation)
- Refactored mixed user controllers to delegate JSON branches to `Users/Api/*`:
  - `Users/WatchlistController`
  - `Users/RatingController`
  - `Users/NotificationController`
- Preserved non-JSON redirect behavior for Blade form submissions.
- Kept route bindings unchanged to avoid contract break.

Artifacts:
- `storage/logs/route-list-after-phase11.json`
- `storage/logs/p11-contract-before.tsv`
- `storage/logs/p11-contract-after.tsv`
- `storage/logs/p11-contract.diff`
- `storage/logs/jav-routes-after-phase11.txt`
- `storage/logs/p11-mixed-routes.tsv`
- `storage/logs/p11-focused-tests.log`
- `storage/logs/p11-focused-tests.exit`

## Current Metrics (from matrix)

| Metric | Value |
|---|---|
| Total classified rows (incl. header) | 94 |
| API routes | 17 |
| Vue page routes | 24 |
| Blade page routes | 23 |
| Shared action routes | 29 |

P2 verification:
- Contract diff lines: `0` (`storage/logs/routes-contract-phase2.diff`)
- Blade GET routes now point to `Modules\\JAV\\Http\\Controllers\\Blade\\...` wrappers.
- Focused feature tests: `35 passed` (`storage/logs/p2-focused-tests.exit=0`).
- Auth feature test: `1 failed, 5 passed` (`storage/logs/p2-auth-test.exit=1`, failure at `Modules/JAV/tests/Feature/AuthTest.php:58`).
- Root cause (deferred): `Modules/JAV/tests/Feature/AuthTest.php` posts `username`, but `Modules/JAV/app/Http/Controllers/Auth/LoginController.php` validates/reads `login`.

P3 verification:
- Old Blade page GET routes under `/jav/*`: none.
- Shared/API contract diff lines: `0` (`storage/logs/p3-shared-contract.diff`).
- Focused feature tests: `36 passed` (`storage/logs/p3-focused-tests.exit=0`).
- Auth feature test remains deferred: `1 failed, 5 passed` (`storage/logs/p3-auth-test.exit=1`).

P4 verification:
- Canonical Vue routes now map to `/jav/*` (`storage/logs/p4-vue-routes.tsv`).
- Legacy `/jav/vue/*` routes are compatibility redirects (`storage/logs/p4-legacy-vue-routes.tsv`).
- Shared/API contract diff lines: `0` (`storage/logs/p4-shared-contract.diff`).
- Focused feature tests: `36 passed` (`storage/logs/p4-focused-tests.exit=0`).
- Auth feature test remains deferred: `1 failed, 5 passed` (`storage/logs/p4-auth-test.exit=1`).

P5 verification:
- Hardcoded `/jav/vue/` path checks in JS: none.
- Frontend build: pass (`storage/logs/p5-build.exit=0`).
- Focused feature tests: `36 passed` (`storage/logs/p5-focused-tests.exit=0`).
- Auth feature test remains deferred: `1 failed, 5 passed` (`storage/logs/p5-auth-test.exit=1`).

P6 verification:
- Legacy `/jav/vue/*` route entries: none (`storage/logs/p6-legacy-vue-routes.tsv` has 0 lines).
- Shared/API contract diff lines: `0` (`storage/logs/p6-shared-contract.diff`).
- Frontend build: pass (`storage/logs/p6-build.exit=0`).
- Focused feature tests: `36 passed` (`storage/logs/p6-focused-tests.exit=0`).
- Auth feature test remains deferred: `1 failed, 5 passed` (`storage/logs/p6-auth-test.exit=1`).
- Deferred compatibility note: GET route name `jav.notifications` was removed; page route is now `jav.vue.notifications` (`/jav/notifications`) and JSON list is `jav.api.notifications.index`.

P7 verification:
- Auth feature test: `6 passed` (`storage/logs/p7-auth-test.exit=0`).
- Notifications feature test: `3 passed` (`storage/logs/p7-notifications-test.exit=0`).
- Legacy GET route-name alias present: `jav.notifications` (`storage/logs/p7-notifications-routes.txt`).

P8 verification:
- Route contract diff lines: `0` (`storage/logs/p8-contract.diff`).
- Vue page routes resolve to new controller domains (Users/Guest) (`storage/logs/p8-vue-routes.txt`).
- Focused feature regression suite: `45 passed` (`storage/logs/p8-focused-tests.exit=0`).

P9 verification:
- Route contract diff lines: `0` (`storage/logs/p9-contract.diff`).
- Admin action routes now point to `Modules\\JAV\\Http\\Controllers\\Admin\\Api\\...` (`storage/logs/p9-admin-api-routes.tsv`).
- Focused feature regression suite: `16 passed` (`storage/logs/p9-focused-tests.exit=0`).

P10 verification:
- Route contract diff lines: `0` (`storage/logs/p10-contract.diff`).
- `jav.toggle-like` and `jav.movies.view` now resolve to `Modules\\JAV\\Http\\Controllers\\Users\\Api\\...` (`storage/logs/p10-user-api-moved-routes.tsv`).
- Focused feature regression suite: `38 passed` (`storage/logs/p10-focused-tests.exit=0`).

P11 verification:
- Route contract diff lines: `0` (`storage/logs/p11-contract.diff`).
- Mixed action route bindings remained unchanged (`storage/logs/p11-mixed-routes.tsv`).
- Focused feature regression suite: `38 passed` (`storage/logs/p11-focused-tests.exit=0`).

Migration-critical shared endpoints (`consumer=Both`):
- `jav.admin.provider-sync.dispatch`
- `jav.admin.search-quality.preview`
- `jav.admin.search-quality.publish`
- `jav.admin.sync-progress.data`
- `jav.preferences.save`
- `jav.presets.save`
- `jav.presets.delete`
- `jav.movies.download`

## Risks Tracked

| Risk | Impact | Mitigation |
|---|---|---|
| Moving `jav.*` too early | Vue admin/actions break | Keep shared actions stable through P4 |
| Blade AJAX route moves without updates | Archived Blade pages break | Use `jav-blade-ajax-usage.txt` as mandatory checklist |
| Mixed controllers refactor + route changes in same PR | Hard rollback/debug | Keep one phase per PR |
| Test environment DB unavailable in sandbox | Cannot run feature assertions end-to-end here | Use route-contract + lint checks now; run full feature suite in CI/local DB |
| Temporary auth payload compatibility (`login` + `username`) | Future ambiguity if UI payload contract is not standardized | Keep for compatibility now; schedule explicit deprecation decision |
| Temporary `jav.notifications` GET alias | Technical debt if kept indefinitely | Keep for compatibility now; set removal criteria/date in future cleanup |

## Migration Status
- Phases P0-P11 are complete.
- Remaining deferred items:
  - Decide long-term contract for login payload key (`login` only vs dual-key support).
  - Decide deprecation/removal timeline for legacy `jav.notifications` route-name alias.

## Verification Commands
```bash
wc -l storage/logs/jav-route-ownership-matrix.csv
awk -F, 'NR>1 {c[$5]++} END {for (k in c) print k, c[k]}' storage/logs/jav-route-ownership-matrix.csv | sort
php artisan route:list > storage/logs/jav-routes-current.txt
diff -u storage/logs/jav-routes-before.txt storage/logs/jav-routes-current.txt
```

## Reference
- Main plan: `JAV_VUE_DEFAULT_MIGRATION_PLAN.md`
