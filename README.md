# XCrawlerII

![Build Status](https://img.shields.io/badge/build-passing-brightgreen)
![PHP Version](https://img.shields.io/badge/php-%5E8.5-blue)
![Laravel Version](https://img.shields.io/badge/laravel-12.x-red)
![License](https://img.shields.io/badge/license-MIT-green)

**XCrawlerII** is an enterprise-grade, modular Laravel platform designed for high-scale crawling, normalization, and indexing of JAV metadata. It features a robust architecture utilizing Elasticsearch for search, Redis for queue management, and MongoDB for telemetry and analytics.

---

## 📖 Table of Contents

-   [About the Project](#about-the-project)
-   [Key Features](#key-features)
-   [Architecture](#architecture)
-   [Prerequisites](#prerequisites)
-   [Installation & Deployment](#installation--deployment)
-   [Local Development](#local-development)
-   [Testing](#testing)
-   [Documentation](#documentation)
-   [Contributing](#contributing)
-   [License](#license)

---

## 🧐 About the Project

XCrawlerII solves the problem of fragmented and inconsistent metadata across multiple JAV source sites. It provides a unified, normalized catalog with powerful search and discovery tools for end-users, while offering deep observability and control for administrators.

## 🚀 Key Features

-   **Multi-Source Ingestion**: Scalable crawling pipeline supporting multiple providers.
-   **Unified Catalog**: Normalized data model for Movies, Actors, and Tags.
-   **Advanced Search**: Powered by Elasticsearch for instant, relevant results.
-   **User Personalization**: Watchlists, Ratings, Favorites, and Browse History.
-   **Observability**: Real-time queue monitoring (Horizon) and detailed telemetry (MongoDB).
-   **Modular Design**: Domain logic isolated in `Modules/` for maintainability.

## 🏗 Architecture

The project follows a **Modular Monolith** architecture.

-   **Code Structure**: See [Code Structure & Architecture](docs/architecture/code_structure.md).
-   **System Overview**: See [Architecture Overview](docs/architecture/overview.md).

**Core Technology Stack:**
-   **Languages**: PHP 8.5, JavaScript (Vue 3).
-   **Frameworks**: Laravel 12, Inertia.js, Tailwind CSS.
-   **Databases**: MySQL 8.0 (Relational), MongoDB 7.0 (Telemetry).
-   **Search**: Elasticsearch 8.x.
-   **Cache & Queues**: Redis 7.x.

## 📋 Prerequisites

Ensure your environment meets the following requirements:

-   **PHP**: 8.5+ (Extensions: `bcmath`, `curl`, `dom`, `gd`, `intl`, `mbstring`, `mysql`, `xml`, `zip`, `mongodb`, `redis`).
-   **Composer**: Latest version.
-   **Node.js**: 22 LTS (with `npm`).
-   **Databases**: MySQL 8+, Redis, MongoDB, Elasticsearch.
-   **Web Server**: Nginx (Recommended) or Apache.

## 📦 Installation & Deployment

For a complete, step-by-step guide on setting up a fresh production server, please refer to the **[Deployment Guide](docs/deployment/deployment-guide.md)**.

> **Note**: This project is optimized for **Nginx**. Using Apache is supported but Nginx is recommended for performance.

## 💻 Local Development

1.  **Clone the repository**
    ```bash
    git clone https://github.com/joosectors/XCrawlerII.git
    cd XCrawlerII
    ```

2.  **Setup Environment**
    ```bash
    composer setup  # Installs PHP deps, copies .env, generates key, installs Node deps, builds assets
    ```

3.  **Configure `.env`**
    Update your database and service credentials in `.env`.

4.  **Start Development Servers**
    ```bash
    composer dev    # Runs Laravel Serve, Queue, Logs, and Vite concurrently
    ```

## 🧪 Testing

We use PHPUnit for backend testing.

```bash
# Run all tests
composer test

# Run specific test suite
php artisan test --testsuite=Feature
```

**Test Reports**:
Integration with CI tools can generate coverage reports. Ensure XDebug or PCOV is installed to generate coverage:
```bash
php artisan test --coverage
```

Code quality checks:
```bash
composer quality:full  # Runs Linting, Static Analysis, and Tests
```

## 📚 Documentation

-   [Deployment Guide](docs/deployment/deployment-guide.md)
-   [Architecture Overview](docs/architecture/overview.md)
-   [Code Structure](docs/architecture/code_structure.md)
-   [User Guide](docs/guides/user-guide.md) (Coming Soon)

## 🤝 Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct, and the process for submitting pull requests.

We enforce code style via `Laravel Pint` and `PHP_CodeSniffer`.

```bash
composer format   # Auto-fix coding style
composer lint     # Check for style issues
```

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
