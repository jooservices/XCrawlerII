# Code Structure & Architecture

This document provides a high-level overview of the XCrawlerII codebase, organized as a Modular Monolith using Laravel Modules.

## High-Level Directory Structure

The project follows a standard Laravel structure but moves domain logic into `Modules/`.

```
XCrawlerII/
├── Modules/                  # Domain-Driven Modules (The Core Logic)
│   ├── Core/                 # Infrastructure, Base Classes, Shared Services
│   └── JAV/                  # Main Domain: Crawlers, Models, UI, Admin
├── app/                      # Framework Glue (Providers, Middleware, bootstrap)
├── config/                   # Global Configuration
├── database/                 # Global Migrations/Seeds (mostly empty, see Modules)
├── docs/                     # Documentation (Architecture, Deployment)
├── public/                   # Web Entry Point (index.php, built assets)
├── resources/                # Global Views (mostly error pages, see Modules for UI)
├── routes/                   # Global Routes (console.php, see Modules for web/api)
└── tests/                    # Global Tests (Integration/E2E)
```

## Modules Breakdown

We use `nwidart/laravel-modules`. Each module is like a mini-Laravel application with its own routes, controllers, views, and database migrations.

### 1. Core Module (`Modules/Core`)

**Purpose**: Cross-cutting concerns, shared utilities, and independent infrastructure components.

-   **`Entities/`**: Base models or shared DTOs.
-   **`Services/`**: Infrastructure services (e.g., `JobTelemetryService` for monitoring queues).
-   **`Resources/`**: Shared Vue components or layouts used across domains.

### 2. JAV Module (`Modules/JAV`)

**Purpose**: The specific business domain (Adult Video metadata aggregation).

-   **`Console/`**: Artisan commands (e.g., `jav:crawl`, `jav:sync-onejav`).
-   **`Database/`**:
    -   `Migrations/`: Tables for `movies`, `actors`, `extras`, etc.
    -   `Seeders/`: Test data generation.
-   **`Entities/`**: Eloquent models (`Movie`, `Actor`, `Onejav`, `XCityIdol`).
-   **`Http/`**:
    -   `Controllers/`: Logic for Dashboard, Search, and Admin panels.
    -   `Requests/`: Form validation.
-   **`Services/`**: Complex business logic (`SearchService`, `CrawlerService`, `NormalizationService`).
-   **`Resources/assets/js/`**: The Vue.js Frontend (Inertia) specific to this domain.
-   **`Tests/`**: Unit and Feature tests specific to JAV logic.

## Key Architectural Decisions

### Frontend (Inertia + Vue 3)
-   The frontend is **monolithic** but modularized.
-   `vite.config.js` is configured to build assets from `Modules/JAV/resources/assets/js/app.js`.
-   We use **Inertia.js** to bridge Laravel and Vue without building a separate API.

### Database
-   **MySQL**: Primary source of truth for relational data (Movies, Actors).
-   **MongoDB**: used for high-velocity `JobTelemetry` events and analytics.
-   **Elasticsearch**: used via `Laravel Scout` for high-performance full-text search.
-   **Redis**: used for Queues (Horizon) and Cache.

### Background Jobs
-   We rely heavily on **Queues**.
-   **Horizon** manages the workers.
-   Crawlers dispatch jobs to specific queues defined in `config/queue.php` (e.g., `jav`, `onejav`, `xcity`).

## Where to find...

-   **Routes**: Look in `Modules/JAV/routes/web.php` for dashboard routes.
-   **Frontend Pages**: Look in `Modules/JAV/resources/assets/js/Pages/`.
-   **Crawler Logic**: Look in `Modules/JAV/Services/Crawlers/`.
-   **Model Definitions**: Look in `Modules/JAV/Entities/`.
