# Deployment Guide

## Server Setup & Prerequisites

These steps assume an Ubuntu 24.04+ environment.

### 1. Install PHP 8.5

Add the Ondrej PHP PPA and install PHP 8.5 with common extensions:

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install php8.5 php8.5-cli php8.5-common php8.5-curl php8.5-mbstring php8.5-xml php8.5-zip php8.5-bcmath php8.5-intl php8.5-gd php8.5-dev php-pear -y
```

### 2. Install Redis and MongoDB Extensions (via PECL)

The project requires `redis` and `mongodb` extensions. Use PECL to install them:

```bash
# Install Redis extension
sudo pecl install redis
echo "extension=redis.so" | sudo tee /etc/php/8.5/mods-available/redis.ini
sudo phpenmod redis

# Install MongoDB extension
sudo pecl install mongodb
echo "extension=mongodb.so" | sudo tee /etc/php/8.5/mods-available/mongodb.ini
sudo phpenmod mongodb
```

### 3. Install Composer

Download and install the latest version of Composer:

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### 4. System Dependencies

The project may require additional system libraries for certain extensions (e.g., `libmongoc` dependencies):

```bash
sudo apt install libcurl4-openssl-dev pkg-config libssl-dev -y
```

### 5. Storage & Permissions

Ensure the storage directory structure exists and is writable by the web server (usually `www-data`):

```bash
# Create necessary directories
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set permissions
sudo chown -R $USER:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

> [!IMPORTANT]
> If `storage/framework/views` is missing, `php artisan` commands will fail with `Please provide a valid cache path`.

### 6. Supervisor Configuration (for Horizon)

To keep Laravel Horizon running automatically, install Supervisor and create a configuration file:

```bash
sudo apt install supervisor -y
```

Create a new configuration file at `/etc/supervisor/conf.d/xcrawler-horizon.conf`:

```ini
[program:xcrawler-horizon]
process_name=%(program_name)s
command=php /home/joos/XCrawlerII/artisan horizon
autostart=true
autorestart=true
user=joos
redirect_stderr=true
stdout_logfile=/home/joos/XCrawlerII/storage/logs/horizon.log
stopwaitsecs=3600
```

Apply the changes:

```bash
sudo reread
sudo update
sudo start xcrawler-horizon
```

### 7. Crontab setup (for Scheduler)

To run the Laravel Scheduler every minute, add a cron entry:

```bash
# Open crontab editor
crontab -e
```

Add the following line to the end of the file:

```bash
* * * * * cd /home/joos/XCrawlerII && php artisan schedule:run >> /dev/null 2>&1
```




## Environments

- Development: local machine, relaxed observability settings.
- Staging: production-like config for validation.
- Production: full worker pools, telemetry retention policy, hardened secrets.

## CI/CD Pipeline Overview

1. Install dependencies.
2. Run quality gate (`composer quality`).
3. Run tests (`composer test`).
4. Build frontend assets.
5. Deploy app code.
6. Run migrations.
7. Restart/reload queue workers and Horizon.
8. Execute smoke checks.

## Release Steps

1. Tag and push release commit.
2. Deploy app and config.
3. Apply migrations.
4. Warm caches if used.
5. Start/reload workers.
6. Verify critical paths and admin health pages.

## Rollback Procedure

1. Revert to previous release artifact/tag.
2. Restart app and workers.
3. If needed, rollback migrations that are safe/reversible.
4. Verify dashboard, sync dispatch, and telemetry endpoints.

## Post-Release Verification

- Dashboard loads and search works.
- Sync dispatch endpoint responds.
- Queue telemetry records new events.
- Error logs contain no sustained critical spikes.
