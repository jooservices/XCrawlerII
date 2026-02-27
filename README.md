# XCrawler

A modular Laravel/Vue crawling platform for metadata discovery, normalization, and advanced search.

## Table of Contents

- [What It Is](#what-it-is)
- [Core Capabilities](#core-capabilities)
- [Why Elasticsearch](#why-elasticsearch)
- [Tech Stack](#tech-stack)
- [Repository Structure](#repository-structure)
- [Usage](#usage)
- [Documentation](#documentation)
- [Quick Start](#quick-start)
- [Verification](#verification)
- [Contribution Workflow](#contribution-workflow)
- [Responsible Crawling](#responsible-crawling)
- [Owner / Contact](#owner--contact)

## What It Is

This repository contains a greenfield crawling platform focused on JAV-related site metadata ingestion and retrieval. The core flow is discover sources, fetch pages/data, parse fields, normalize metadata into stable contracts, and expose fast structured search APIs. The architecture is extensible to support additional content domains over time.

## Core Capabilities

- Crawling pipeline stages: discover -> fetch -> parse -> normalize.
- Metadata normalization into consistent schemas across heterogeneous sources.
- Advanced search and filtering powered by Elasticsearch, including structured and range-based filters at a high level.
- Extensible source support so new target types can be added without rewriting the core platform.

## Why Elasticsearch

- Fast structured filtering over normalized metadata fields.
- Efficient range queries for numeric/date-like criteria (high-level use cases).
- Flexible indexing model for combining exact filters and relevance-based retrieval.

## Tech Stack

- Backend: Laravel 12, PHP 8.5 (project baseline).
- Frontend: Vue 3 + Inertia.js.
- Data/search services: MariaDB, MongoDB, Redis, Elasticsearch.
- Architecture: Laravel modules package (`nwidart/laravel-modules`) with `Modules/Core` as shared/general.
- Required boundary packages: `jooservices/dto`, `jooservices/client`.

## Repository Structure

- `Modules/Core`: shared contracts, enums/constants, shared DTOs, FE master layout and base/shared components.
- `Modules/<Feature>`: feature-owned routes, controllers, requests, services, repositories, models, pages/components/tests.
- FE concept: Core FE owns master layout + shared building blocks; feature FE owns feature pages/components.

## Usage

### A) Add a New Crawler Target/Source

Start from module boundaries and delivery gates:

- [Module Boundaries & Dependencies](docs/architecture/01-module-boundaries-and-dependencies.md)
- [Feature Definition of Done](docs/architecture/09-feature-definition-of-done.md)
- [Database Standards](docs/architecture/06-database-standards.md)

### B) Add a New Searchable Metadata Field

Update persistence + index contract with canonical standards:

- [Database Standards](docs/architecture/06-database-standards.md)
- [API Contracts (REST + schema)](docs/architecture/05-api-contracts-restful.md)

### C) Add a New Search Filter Endpoint

Follow API and testing constitutions:

- [Routing & Controllers Standard](docs/architecture/02-routing-and-controllers-standard.md)
- [API Contracts RESTful](docs/architecture/05-api-contracts-restful.md)
- [Testing Constitution](docs/architecture/07-testing-constitution.md)

## Documentation

- Architecture index: [docs/architecture/README.md](docs/architecture/README.md)
- Skills index: [docs/skills/README.md](docs/skills/README.md)
- AI operating policy: [docs/skills/ai-operating-policy.md](docs/skills/ai-operating-policy.md)
- Skill catalog: [docs/skills/skill-catalog.md](docs/skills/skill-catalog.md)

Architecture set (00-14):

- Structure & module design:
    - [00 Project Structure](docs/architecture/00-project-structure.md)
    - [01 Module Boundaries](docs/architecture/01-module-boundaries-and-dependencies.md)
- Routing/API/back-end/front-end:
    - [02 Routing & Controllers](docs/architecture/02-routing-and-controllers-standard.md)
    - [03 Backend Rules](docs/architecture/03-backend-architecture-rules.md)
    - [04 Frontend Rules](docs/architecture/04-frontend-architecture-rules.md)
    - [05 API Contracts RESTful](docs/architecture/05-api-contracts-restful.md)
- Data/testing/quality:
    - [06 Database Standards](docs/architecture/06-database-standards.md)
    - [07 Testing Constitution](docs/architecture/07-testing-constitution.md)
    - [08 Quality Toolchain](docs/architecture/08-quality-toolchain.md)
    - [09 Feature DoD](docs/architecture/09-feature-definition-of-done.md)
    - [10 DTO and Client Policy](docs/architecture/10-dto-and-client-policy.md)
    - [11 Exceptions Registry](docs/architecture/11-exceptions-registry.md)
    - [12 Git Hooks and Quality Gates](docs/architecture/12-git-hooks-and-quality-gates.md)
    - [13 Coverage Policy](docs/architecture/13-coverage-policy.md)
    - [14 Branch, Commit, PR Rules](docs/architecture/14-branch-commit-pr-rules.md)

## Quick Start

Prerequisites:

- PHP, Composer, Node.js/npm

Install and bootstrap:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
```

Run development:

```bash
composer dev
```

Alternative split run:

```bash
php artisan serve
npm run dev
```

Elasticsearch (Docker):

```bash
docker compose up -d
```

Service health check:

```bash
php artisan services:health
```

## Verification

Run quality gates locally:

```bash
./vendor/bin/pint --test
./vendor/bin/phpunit
./vendor/bin/phpstan analyse
./vendor/bin/phpcs
./vendor/bin/phpmd app,Modules text phpmd.xml
npx vitest run
npx playwright test
```

Policy references:

- [Git Hooks and Quality Gates](docs/architecture/12-git-hooks-and-quality-gates.md)
- [Coverage Policy (>=90%, target 100%)](docs/architecture/13-coverage-policy.md)

## Contribution Workflow

- Long-lived branches: `develop` (integration), `master` (production).
- Feature/fix branches flow into `develop`.
- Hotfix branches flow from/to `master`, then mandatory back-merge `master -> develop`.
- Conventional Commits required.

See canonical workflow rules:

- [Branch, Commit, PR Rules](docs/architecture/14-branch-commit-pr-rules.md)

## Responsible Crawling

Respect target site constraints (for example robots.txt and rate limiting) and keep crawler behavior controlled and observable.

## Owner / Contact

- Owner: Viet Vu
- Email: [jooservices@gmail.com](mailto:jooservices@gmail.com)
- Website: [https://jooservices.com](https://jooservices.com)
