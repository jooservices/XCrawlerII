# XCrawlerII JAV Movie Card Handover

Date: 2026-02-18
Owner: GitHub Copilot (GPT-5.2-Codex)
Branch: vietvu/feature-stakeholder-feedback-update

## Audience
- SA (Solutions Architect): overview, system impact, data flow.
- Developer: implementation detail, code map, tests, and next steps.

## Executive Summary (What / Why / How)
- What: Completed wiring and verification paths for movie card features across FE/BE, added featured system, unified per-user decoration, aligned favorites type handling, and improved test infrastructure with CSRF coverage. Added ES sync verification steps and search integration checks.
- Why: Movie card features were not consistently persisted or rehydrated across pages. Featured status was missing from API decorations. Favorites type handling broke due to morph alias vs class names. Tests were failing due to CSRF and missing state in decorated lists.
- How: Added a single decoration pipeline in the dashboard repository, updated controllers to use it, added featured lookup, adjusted FE to accept aliases, and strengthened request/tests for CSRF. Validated ES sync, search query path, and UI endpoints.

## Expected Outcomes
- SA: All movie-card actions now share a consistent data contract, user-specific flags are decorated in lists, and featured content is managed via admin API with stable ES indexing flow.
- Developer: The data path for like/watchlist/rate/featured/view/download is consistent across dashboard, related, recommendations, and favorites. Tests should be green after CSRF changes; remaining failures are command/job-related (see Testing).

## Scope of Work (What Changed)
### Frontend
- Movie card composition consolidated around `MovieCard.vue` and shared actions in `BaseCard.vue`.
- Featured toggle UI added via `FeaturedQuickAction.vue`.
- Favorites view accepts both morph alias and legacy class name for types.
- Layout and dashboard sections updated to consume decorated list data consistently.

Key files:
- Modules/JAV/resources/js/Components/MovieCard.vue
- Modules/JAV/resources/js/Components/BaseCard.vue
- Modules/JAV/resources/js/Components/PrimeMovieCardFull.vue
- Modules/JAV/resources/js/Components/FeaturedQuickAction.vue
- Modules/JAV/resources/js/Pages/Dashboard/Index.vue
- Modules/JAV/resources/js/Pages/User/Favorites.vue
- Modules/JAV/resources/js/Pages/User/Recommendations.vue

### Backend
- Unified decoration of per-user flags in `DashboardReadRepository` (likes, watchlist, rating, featured).
- Controllers updated to apply decoration for dashboard, actor bio, related lists, favorites, and recommendations.
- Featured model + admin API endpoints added for featured items.
- Interaction model + repository added to normalize ratings and user interaction storage.

Key files:
- Modules/JAV/app/Repositories/DashboardReadRepository.php
- Modules/JAV/app/Http/Controllers/Users/DashboardController.php
- Modules/JAV/app/Http/Controllers/Users/MovieController.php
- Modules/JAV/app/Http/Controllers/Users/ActorController.php
- Modules/JAV/app/Http/Controllers/Users/LibraryController.php
- Modules/JAV/app/Http/Controllers/Users/Api/RatingController.php
- Modules/JAV/app/Models/Interaction.php
- app/Models/FeaturedItem.php
- Modules/JAV/app/Http/Controllers/Admin/Api/FeaturedItemsController.php

### Data + Search
- Elasticsearch sync command verified and still used as the canonical indexer.
- SearchService remains the gateway (ES or DB fallback), now compatible with decorated movie card UI state.

Key files:
- Modules/JAV/app/Services/SearchService.php
- Modules/JAV/app/Console/JavSyncSearchCommand.php

## Code Structure Overview
- Modules/JAV
  - app/Http/Controllers/Users/*: Vue page controllers and API controllers for movie card actions.
  - app/Repositories/DashboardReadRepository.php: central decoration pipeline for user-state flags.
  - app/Models/Jav.php: movie entity, ES searchable payload.
  - app/Models/Interaction.php: ratings/interactions via morph aliases.
  - app/Models/FeaturedItem.php: featured content store.
  - resources/js/Components: MovieCard/BaseCard/FeaturedQuickAction.
  - resources/js/Pages: Dashboard, Movie detail, Favorites, Recommendations.
  - routes/web.php: API and Vue routes for movie interactions.

## Logic / Process Flow
### Movie card state hydration
1. Controller fetches list (dashboard, actor bio, related, favorites, recommendations).
2. `DashboardReadRepository::decorateJavsForUser()` merges per-user flags:
   - is_liked, in_watchlist, user_rating, user_rating_id
   - is_featured (from FeaturedItem lookup)
3. UI renders `MovieCard.vue` -> `BaseCard.vue` using flags.

### User actions
- Like/watchlist/rate: FE sends JSON to web API routes under /jav/api; BE persists via Interaction/Watchlist/Rating flows.
- Featured: admin API writes to FeaturedItem and updates flags in decoration.
- View/download: routes increment view/download counts and are reflected in ES sync.

### Mermaid diagram
```mermaid
flowchart LR
  A[User List Page] --> B[Controller Fetch List]
  B --> C[DashboardReadRepository::decorateJavsForUser]
  C --> D[MovieCard.vue]
  D --> E[BaseCard.vue]
  E --> F{Action}
  F -->|Like| G[/jav/api/like]
  F -->|Watchlist| H[/jav/api/watchlist]
  F -->|Rate| I[/jav/api/ratings]
  F -->|Featured| J[/jav/api/admin/featured-items]
  F -->|View| K[/jav/movies/{jav}/view]
  F -->|Download| L[/jav/movies/{jav}/download]
  G --> M[(DB: interactions)]
  H --> N[(DB: watchlists)]
  I --> M
  J --> O[(DB: featured_items)]
  K --> P[(DB: jav.views)]
  L --> P
  M --> Q[Decorator Flags]
  O --> Q
  Q --> D
```

## Testing Status
- Full test suite was run before the cache-flush change. Initial failures were dominated by CSRF 419 for JSON routes and fixtures; CSRF headers are now injected for all non-GET requests via base TestCase.
- Remaining failures are primarily command/job dispatch expectations (queue uniqueness and cache locks) and JavService fixture ingestion. Cache flushing was added to test setup to reduce lock collisions; rerun tests to confirm current status.

Known failing clusters (to validate after next run):
- Command dispatch tests (DailySync, Ffjav/Onejav/141jav commands, provider sync) may fail if queue unique locks or cache keys persist.
- JavServiceTest fixture ingestion (DB not getting items) indicates event subscriber or queue settings interfering with ItemParsed processing.

## Operational Notes
- CSRF tokens are now auto-applied to all POST/PUT/PATCH/DELETE test requests via tests/TestCase.php.
- Cache is flushed in test setup to avoid unique job lock or rate-limit conflicts.
- ES sync validated via `php artisan jav:sync:search` and SearchService sample query.

## Developer Handover Checklist
- Re-run tests: `php artisan test`.
- If command tests fail, verify:
  - Cache state (unique locks): ensure Cache::flush() runs in base TestCase.
  - Queue connection is `sync` in phpunit config.
- If JavServiceTest fails to store data:
  - Confirm ItemParsed listener is loaded and not queued to a non-sync queue.
  - Validate that ItemParsed -> JavSubscriber -> JavManager::store is running.

## Expectations for Next Owner
- Confirm remaining test failures and address as needed; do not alter behavior of CSRF for web routes.
- Validate featured toggle on admin UI and that decoration includes is_featured in all movie lists.
- Confirm ES sync and SearchService behavior in a real environment.

## Implementation Notes (Why these choices)
- Centralizing decoration avoids drift across controllers and pages.
- Featured status is computed once to keep FE consistent and avoid per-page queries.
- CSRF test support keeps web routes protected while allowing reliable tests.

## Verification Steps (Manual)
- Dashboard: movie cards show like/watchlist/rating/featured state after reload.
- Favorites page: items render regardless of morph alias or legacy type.
- Related movies: status flags are consistent with main list.
- Search: results appear after ES sync.

## Files Modified (partial)
- See git status for full list; primary changes are in Modules/JAV (controllers, repository, components, models, tests) and tests/TestCase.php.
