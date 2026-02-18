# Analytics & Event Tracking Architecture

## Overview

This document defines the event tracking, analytics counting, and data storage
strategy for all user interactions and system events.

---

## 1. What We Track

### User Interaction Events

| Event | Description | Who | Missing from current codebase |
|-------|-------------|-----|-------------------------------|
| `movie.viewed` | User (or guest) opens a movie detail page | user_id (nullable) + ip | Partially — `user_jav_history` exists but incomplete |
| `movie.downloaded` | User clicks download link | user_id (nullable) + ip | **Missing** |
| `movie.rated` | User submits a rating (1–5 stars) | user_id | In `user_interactions` |
| `movie.liked` | User likes a movie | user_id | In `user_interactions` |
| `movie.unliked` | User removes a like | user_id | In `user_interactions` (delete) |
| `movie.watchlisted` | User adds movie to watchlist | user_id | In `watchlists` table |
| `movie.unwatchlisted` | User removes from watchlist | user_id | In `watchlists` table |
| `actor.viewed` | User opens an actor bio page | user_id (nullable) + ip | **Missing** |
| `tag.viewed` | User visits a tag page | user_id (nullable) + ip | **Missing** — low priority |
| `search.performed` | User submits a search query | user_id (nullable) + query + filters | **Missing** |
| `recommendation.clicked` | User clicked a recommendation | user_id + jav_id + reason | **Missing** |

### Admin Events

| Event | Description |
|-------|-------------|
| `featured.item.created` | Admin adds item to featured |
| `featured.item.updated` | Admin changes rank/group/expiry |
| `featured.item.removed` | Admin deactivates featured item |

### What you are NOT missing (already covered)

- Rating ✓ (`user_interactions` with `action = 'rating'`)
- Like ✓ (`user_interactions` with `action = 'favorite'`)
- Watchlist ✓ (`watchlists` table)
- Featured ✓ (`featured_items` table)

### What you ARE missing (recommend adding)

1. **Download log** — `jav.downloads` counter exists but no event log (who/when/ip)
2. **Actor view log** — no tracking at all
3. **Search query log** — valuable for knowing what users look for (analytics + improving ES index quality)
4. **Recommendation click tracking** — know if recommendations actually work

---

## 2. Storage Strategy Per Data Type

```
┌──────────────────────────────────────────────────────────────────────┐
│ WRITE PATH (on user action)                                          │
│                                                                      │
│  User Action → Laravel Event (queued) → Multiple Listeners          │
│                                                                      │
│  Listener A: Log raw event → MySQL event log table (who/ip/when)    │
│  Listener B: Increment counter → Redis INCR (fast, atomic)          │
│  Listener C: Update user state → MySQL (history, watchlist, etc.)   │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ FLUSH PATH (scheduled jobs)                                          │
│                                                                      │
│  Every 5 min:  Redis counters → batch UPDATE MySQL jav.views/downloads│
│  Every 1 hour: MySQL jav.views/downloads → ES index re-sync         │
│  Every 30 min: MySQL aggregations → MongoDB AnalyticsSnapshot cache  │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ READ PATH                                                            │
│                                                                      │
│  Page display counts:    Redis (fast, may lag by ~5 min)            │
│  Admin analytics charts: MongoDB AnalyticsSnapshot (30 min cache)   │
│  Search sort by views:   ES (1 hour lag acceptable for sort)        │
│  User history/likes:     MySQL directly (needs to be accurate)      │
│  Featured items:         Redis cache (invalidated on write)         │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 3. Per-Storage Role

### MySQL — Source of Truth

**Tables:**

| Table | Purpose |
|-------|---------|
| `jav` | `views` and `downloads` integer counters (authoritative) |
| `user_interactions` | likes, ratings (one row per user per item) |
| `user_jav_history` | view log per user (extend with ip, user_agent) |
| `watchlists` | watchlist entries |
| `featured_items` | featured items definition (already exists) |
| `view_events` (NEW) | raw view log: guest + logged-in, ip, user_agent, timestamp |
| `download_events` (NEW) | raw download log: same fields |
| `search_events` (NEW) | search query log: query, filters, result_count, user_id/ip |

**Why MySQL for event logs:** Moderate traffic, need exact user attribution, deduplication queries.
Replace with a time-series DB (ClickHouse, TimescaleDB) only if event volume becomes millions/day.

---

### Redis — Real-Time Counters + Featured Cache

**Keys:**

```
jav:views:{jav_id}           → INCR on every view
jav:downloads:{jav_id}       → INCR on every download
jav:likes:{jav_id}           → INCR on like, DECR on unlike
jav:featured:all             → JSON payload of active featured items (TTL 10 min)
jav:featured:group:{group}   → JSON payload per group (TTL 10 min)
```

**Rules:**
- On like/unlike: also INCR/DECR `jav:likes:{jav_id}` alongside MySQL write
- On featured create/update/remove: `Redis::del('jav:featured:all')` + per-group key
- Redis counters are the display value shown to users (fast)
- Redis counters are flushed to MySQL every 5 minutes via scheduled job

---

### MongoDB — Pre-Computed Analytics Snapshots

**Collections (existing):**

| Collection | Purpose |
|------------|---------|
| `analytics_snapshots` | Pre-computed dashboard payload: top viewed, daily trends, provider stats |
| `recommendation_snapshots` | Pre-computed recommendations per user |

**Rules:**
- Never write to MongoDB on user action (too slow for hot path)
- Only write from scheduled artisan jobs
- Refresh interval: 30 minutes (current) — keep as-is
- MongoDB here is a **read cache**, not a source of truth
- If MongoDB is down, fall back to MySQL (already implemented)

---

### Elasticsearch — Search + Sort Only

**Indices:**

| Index | Contains |
|-------|---------|
| `jav` | All movie fields including `views`, `downloads` (for sort-by-popularity) |
| `actors` | All actor fields |
| `tags` | Tag names |

**Rules:**
- ES is **never written to directly on user action**
- `views` and `downloads` in ES are updated via hourly re-sync from MySQL
- A 1-hour lag is acceptable for sort-by-views in search results
- ES is for search relevance and sorting — never for counting

---

## 4. Laravel Events to Create

### New Events (add to `Modules/JAV/app/Events/Domain/`)

```php
// Modules/JAV/app/Events/Domain/MovieViewed.php
// Properties: int $javId, ?int $userId, string $ip, ?string $userAgent, Carbon $viewedAt

// Modules/JAV/app/Events/Domain/MovieDownloaded.php
// Properties: int $javId, ?int $userId, string $ip, ?string $userAgent, Carbon $downloadedAt

// Modules/JAV/app/Events/Domain/ActorViewed.php
// Properties: int $actorId, ?int $userId, string $ip, ?string $userAgent, Carbon $viewedAt

// Modules/JAV/app/Events/Domain/SearchPerformed.php
// Properties: string $query, array $filters, int $resultCount, ?int $userId, string $ip

// Modules/JAV\app/Events/Domain/RecommendationClicked.php
// Properties: int $javId, ?string $reason, int $userId, Carbon $clickedAt
```

### Existing events already cover

- `MovieLiked` / `MovieUnliked` → via `user_interactions` table changes
- `FeaturedItem` CRUD → trigger cache invalidation via model observer

---

## 5. Listeners Per Event

### `MovieViewed`

```
Listeners (all queued, non-blocking):
  1. LogViewEvent          → INSERT into view_events (ip, user_id, jav_id, user_agent, ts)
  2. IncrementViewCounter  → Redis INCR jav:views:{jav_id}
  3. UpdateUserHistory     → INSERT/UPDATE user_jav_history (only if user logged in)
```

### `MovieDownloaded`

```
Listeners:
  1. LogDownloadEvent      → INSERT into download_events
  2. IncrementDownloadCounter → Redis INCR jav:downloads:{jav_id}
  3. UpdateUserHistory     → mark as downloaded in user_jav_history (if logged in)
```

### `MovieRated` / `MovieLiked`

```
Listeners:
  1. (already handled by user_interactions insert — synchronous, needs user attribution)
  2. IncrementLikeCounter  → Redis INCR jav:likes:{jav_id}  (on like)
                             Redis DECR jav:likes:{jav_id}  (on unlike)
```

### `FeaturedItem` created/updated/deleted (Model Observer)

```
Listeners:
  1. InvalidateFeaturedCache → Redis::del(['jav:featured:all', 'jav:featured:group:{group}'])
```

### `SearchPerformed`

```
Listeners:
  1. LogSearchEvent → INSERT into search_events (query, filters JSON, result_count, user_id/ip)
  Note: fire this only when result_count > 0, or always — your choice
```

---

## 6. Scheduled Jobs

| Job | Frequency | What it does |
|-----|-----------|-------------|
| `FlushViewCountersJob` | Every 5 min | Redis `jav:views:*` → batch UPDATE MySQL `jav.views` |
| `FlushDownloadCountersJob` | Every 5 min | Redis `jav:downloads:*` → batch UPDATE MySQL `jav.downloads` |
| `FlushLikeCountersJob` | Every 5 min | Redis `jav:likes:*` → (optional) UPDATE denormalized like count |
| `SyncCountsToElasticsearch` | Every 1 hour | MySQL `jav.views`, `jav.downloads` → ES index bulk update |
| `RebuildAnalyticsSnapshot` | Every 30 min | MySQL aggregations → MongoDB AnalyticsSnapshot |
| `RebuildRecommendations` | Configurable | MongoDB RecommendationSnapshot |

---

## 7. Full Process Flow — Movie View

```
User opens /movies/{uuid}
      │
      ▼
MovieController@show
      │
      ├─ Render page (Inertia response — do NOT block on analytics)
      │
      └─ MovieViewed::dispatch($jav->id, auth()->id(), $request->ip(), $request->userAgent())
                │
                │  (queued — async, does not block the HTTP response)
                ▼
         ┌──────────────────────────────────────┐
         │ Queue Worker processes event          │
         │                                      │
         │  LogViewEvent listener:              │
         │    INSERT view_events (...)           │
         │                                      │
         │  IncrementViewCounter listener:      │
         │    Redis INCR jav:views:{id}          │
         │                                      │
         │  UpdateUserHistory listener:          │
         │    INSERT/UPDATE user_jav_history     │
         │    (only if authenticated)            │
         └──────────────────────────────────────┘
                │
                │  (every 5 minutes, scheduled job)
                ▼
         Redis counters → MySQL jav.views (batch UPDATE)
                │
                │  (every 1 hour, scheduled job)
                ▼
         MySQL jav.views → Elasticsearch jav index (bulk update)
```

---

## 8. Featured Items — Cache Strategy

**Problem:** Featured items are queried on every dashboard page load.

**Solution:** Redis cache with event-driven invalidation.

```
READ (DashboardController):
  $featured = Redis::get('jav:featured:all');
  if (!$featured) {
      $featured = FeaturedItem::query()->with('item')->where(...)->get();
      Redis::setex('jav:featured:all', 600, json_encode($featured)); // 10 min TTL
  }

WRITE (FeaturedItem Model Observer):
  on created/updated/deleted:
      Redis::del('jav:featured:all');
      Redis::del('jav:featured:group:' . $item->group);
```

**Why Redis, not MongoDB?**
- Featured data is structural (active items, rank, group) — not analytics
- Needs invalidation on admin write — Redis supports this cleanly
- MongoDB is for pre-computed analytics payloads, not live app data cache

---

## 9. New Database Tables Needed

### `view_events`

```sql
CREATE TABLE view_events (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jav_id       BIGINT UNSIGNED NOT NULL,
    user_id      BIGINT UNSIGNED NULL,
    ip           VARCHAR(45) NOT NULL,
    user_agent   TEXT NULL,
    viewed_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_jav_id (jav_id),
    INDEX idx_user_id (user_id),
    INDEX idx_viewed_at (viewed_at)
);
```

### `download_events`

```sql
CREATE TABLE download_events (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jav_id         BIGINT UNSIGNED NOT NULL,
    user_id        BIGINT UNSIGNED NULL,
    ip             VARCHAR(45) NOT NULL,
    user_agent     TEXT NULL,
    downloaded_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_jav_id (jav_id),
    INDEX idx_user_id (user_id),
    INDEX idx_downloaded_at (downloaded_at)
);
```

### `search_events`

```sql
CREATE TABLE search_events (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    query        VARCHAR(500) NOT NULL DEFAULT '',
    filters      JSON NULL,
    entity_type  ENUM('movie', 'actor', 'tag') NOT NULL DEFAULT 'movie',
    result_count INT UNSIGNED NOT NULL DEFAULT 0,
    user_id      BIGINT UNSIGNED NULL,
    ip           VARCHAR(45) NOT NULL,
    searched_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_searched_at (searched_at),
    INDEX idx_entity_type (entity_type)
);
```

### `actor_view_events`

```sql
CREATE TABLE actor_view_events (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_id   BIGINT UNSIGNED NOT NULL,
    user_id    BIGINT UNSIGNED NULL,
    ip         VARCHAR(45) NOT NULL,
    viewed_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_actor_id (actor_id),
    INDEX idx_viewed_at (viewed_at)
);
```

---

## 10. Architecture Decision Summary

| Data | Write To | Read From | Why |
|------|----------|-----------|-----|
| Raw view/download events | MySQL (via queue) | MySQL for admin queries | Exact, queryable, moderate volume |
| View/download counts (display) | Redis INCR | Redis | Fast, atomic, ~5 min lag acceptable |
| View/download counts (truth) | MySQL (flushed from Redis) | MySQL | Authoritative after flush |
| Like/rating records | MySQL (sync, needs attribution) | MySQL | Must be exact, user owns data |
| Featured items (live) | MySQL (admin writes) | Redis cache | Invalidated on write, fast reads |
| Analytics dashboard | MongoDB (computed cache) | MongoDB | Avoid repeated heavy aggregations |
| Search sort (views/downloads) | ES (synced hourly from MySQL) | ES | Search relevance, 1h lag fine |
| Search query log | MySQL | MySQL | Low volume, queryable |

---

## 11. What To Build — Implementation Order

### Phase 1 — Core counters (high value, fast to build)

1. `FlushViewCountersJob` — flush Redis → MySQL (need this before Phase 2)
2. `MovieViewed` event + 3 listeners
3. `MovieDownloaded` event + 2 listeners
4. Featured items Redis cache + observer invalidation

### Phase 2 — Logging (visibility into user behavior)

5. `view_events` migration + `LogViewEvent` listener
6. `download_events` migration + `LogDownloadEvent` listener
7. `actor_view_events` migration + `ActorViewed` event

### Phase 3 — Search intelligence

8. `search_events` migration + `SearchPerformed` event
9. Admin UI to view popular search queries

### Phase 4 — ES sync improvement

10. `SyncCountsToElasticsearch` scheduled job (views/downloads → ES hourly)
11. Implement `searchTagsViaElasticsearch()` in SearchService (tags never used ES)
