# Deployment Guide

## Server Setup & Prerequisites

These steps assume an Ubuntu 24.04+ environment.

### 1. Install PHP 8.5

Add the Ondrej PHP PPA and install PHP 8.5 with common extensions:

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install php8.5 php8.5-cli php8.5-common php8.5-curl php8.5-mbstring php8.5-xml php8.5-zip php8.5-bcmath php8.5-intl php8.5-gd php8.5-mysql php8.5-dev php-pear -y
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

### 4. Frontend Setup (Node.js + Vite)

This project uses Vite + Vue + Tailwind and must build frontend assets before serving in staging/production.

Install Node.js 22 LTS (includes npm):

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

Install frontend packages from `package-lock.json`:

```bash
npm ci
```

If `package-lock.json` is not available, install the required frontend packages explicitly:

```bash
npm install vue @inertiajs/vue3 @tanstack/vue-query pinia primevue primeicons @primeuix/themes @vuepic/vue-datepicker apexcharts vue3-apexcharts swiper ziggy-js @vitejs/plugin-vue
npm install -D vite laravel-vite-plugin tailwindcss @tailwindcss/vite axios concurrently @types/node
```

Build production assets:

```bash
npm run build
```

For local/dev environments, run Vite dev server:

```bash
npm run dev
```

Frontend-related environment configuration (`.env`):

```dotenv
APP_URL=https://your-domain.example
ASSET_URL=https://your-domain.example
```

If your deployment serves static files from the same domain as Laravel, `ASSET_URL` can be omitted.

Vite configuration used by this project (`vite.config.js`):

- Input files: `resources/css/app.css`, `resources/js/app.js`, `Modules/JAV/resources/css/dashboard-shared.css`, `Modules/JAV/resources/js/app.js`
- Plugins: `laravel-vite-plugin`, `@vitejs/plugin-vue`, `@tailwindcss/vite`
- Aliases: `@` -> `/resources/js`, `@jav` -> `/Modules/JAV/resources/js`

For zero-downtime deployments, install dependencies and build assets during release:

```bash
npm ci --no-audit --no-fund
npm run build
```

### 5. Environment Configuration (`.env`)

Copy the template and generate app key:

```bash
cp .env.example .env
php artisan key:generate
```

> [!WARNING]
> Never commit real credentials, tokens, or private endpoints into git. Keep secrets in deployment secrets manager / CI variables.

Recommended production `.env` structure (sanitized):

```dotenv
APP_NAME=XCrawlerII
APP_ENV=production
APP_KEY=base64:GENERATED_BY_ARTISAN_KEY_GENERATE
APP_DEBUG=false
APP_URL=https://your-domain.example
ASSET_URL=https://your-domain.example

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=xcrawlerii
DB_USERNAME=xcrawlerii
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis

CACHE_STORE=redis
MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

SCOUT_DRIVER=Matchish\ScoutElasticSearch\Engines\ElasticSearchEngine
ELASTICSEARCH_HOST=http://127.0.0.1:9200

SHOW_COVER=false

MONGODB_URI=
MONGODB_HOST=127.0.0.1
MONGODB_PORT=27017
MONGODB_DATABASE=xcrawler_analytics
MONGODB_USERNAME=
MONGODB_PASSWORD=
MONGODB_AUTH_DATABASE=admin

JOB_TELEMETRY_ENABLED=true
JOB_TELEMETRY_RETENTION_DAYS=30
JOB_TELEMETRY_TIMER_TTL_SECONDS=3600
JOB_TELEMETRY_AUTO_CREATE_INDEXES=true
JOB_TELEMETRY_RATE_ENABLED=true
JOB_TELEMETRY_RATE_WARNING_PER_SECOND=20
JOB_TELEMETRY_RATE_CRITICAL_PER_SECOND=40

JAV_IDOL_QUEUE=xcity
JAV_ONEJAV_QUEUE=onejav
JAV_141_QUEUE=141
JAV_FFJAV_QUEUE=jav
```

Variable reference:

- `APP_NAME`: application display name.
- `APP_ENV`: runtime environment (`production`, `staging`, `local`).
- `APP_KEY`: Laravel encryption key (must be generated, never hardcode shared key).
- `APP_DEBUG`: disable in production (`false`).
- `APP_URL`: canonical backend URL.
- `ASSET_URL`: optional asset base URL / CDN URL.

- `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE`: language and faker locale settings.

- `APP_MAINTENANCE_DRIVER`: maintenance mode storage backend.
- `BCRYPT_ROUNDS`: password hash work factor.

- `LOG_CHANNEL`: default log driver.
- `LOG_STACK`: channels included when using stack driver.
- `LOG_DEPRECATIONS_CHANNEL`: deprecation log channel.
- `LOG_LEVEL`: minimum log level threshold.

- `DB_CONNECTION`: primary SQL driver (`mysql` for this deployment).
- `DB_HOST`, `DB_PORT`: MySQL server endpoint.
- `DB_DATABASE`: MySQL database name.
- `DB_USERNAME`, `DB_PASSWORD`: MySQL credentials (secret values).

- `SESSION_DRIVER`: session backend (`database` recommended here).
- `SESSION_LIFETIME`: session duration in minutes.
- `SESSION_ENCRYPT`: session payload encryption toggle.
- `SESSION_PATH`: cookie path.
- `SESSION_DOMAIN`: cookie domain (set for subdomain sharing if needed).

- `BROADCAST_CONNECTION`: broadcast driver.
- `FILESYSTEM_DISK`: default filesystem disk.
- `QUEUE_CONNECTION`: queue backend (`redis` in production).

- `CACHE_STORE`: default cache backend (`redis`).
- `MEMCACHED_HOST`: memcached host (used only if memcached cache driver enabled).

- `REDIS_CLIENT`: Redis PHP client (`phpredis`).
- `REDIS_HOST`, `REDIS_PORT`: Redis endpoint.
- `REDIS_PASSWORD`: Redis auth secret (if required by server).

- `MAIL_MAILER`: mail transport (`log`, `smtp`, etc.).
- `MAIL_SCHEME`: transport scheme (`tls`, `ssl`, or null).
- `MAIL_HOST`, `MAIL_PORT`: mail server endpoint.
- `MAIL_USERNAME`, `MAIL_PASSWORD`: mail credentials.
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`: default sender identity.

- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`: object storage credentials.
- `AWS_DEFAULT_REGION`: cloud region.
- `AWS_BUCKET`: bucket name.
- `AWS_USE_PATH_STYLE_ENDPOINT`: S3-compatible endpoint mode toggle.

- `VITE_APP_NAME`: frontend-exposed app name variable.

- `SCOUT_DRIVER`: Laravel Scout engine class.
- `ELASTICSEARCH_HOST`: Elasticsearch endpoint (include auth only via secrets).

- `SHOW_COVER`: feature toggle for JAV cover display.

- `MONGODB_URI`: optional full Mongo connection string.
- `MONGODB_HOST`, `MONGODB_PORT`: MongoDB endpoint.
- `MONGODB_DATABASE`: MongoDB database for analytics.
- `MONGODB_USERNAME`, `MONGODB_PASSWORD`: MongoDB credentials.
- `MONGODB_AUTH_DATABASE`: Mongo authentication database.

- `JOB_TELEMETRY_ENABLED`: enable telemetry capture.
- `JOB_TELEMETRY_RETENTION_DAYS`: telemetry retention period.
- `JOB_TELEMETRY_TIMER_TTL_SECONDS`: timer key TTL.
- `JOB_TELEMETRY_AUTO_CREATE_INDEXES`: auto-create Mongo indexes.
- `JOB_TELEMETRY_RATE_ENABLED`: enable rate alerts.
- `JOB_TELEMETRY_RATE_WARNING_PER_SECOND`: warning threshold.
- `JOB_TELEMETRY_RATE_CRITICAL_PER_SECOND`: critical threshold.

- `JAV_IDOL_QUEUE`: queue name for idol sync jobs.
- `JAV_ONEJAV_QUEUE`: queue name for OneJAV jobs.
- `JAV_141_QUEUE`: queue name for 141JAV jobs.
- `JAV_FFJAV_QUEUE`: queue name for FFJAV jobs.

### 6. Apache Setup (Web Server + Rewrite)

Yes — Apache is supported for this Laravel app, and `mod_rewrite` is required.

Install Apache and PHP-FPM integration:

```bash
sudo apt update
sudo apt install -y apache2 php8.5-fpm libapache2-mod-fcgid
```

Enable required Apache modules and PHP-FPM config:

```bash
sudo a2enmod rewrite headers expires proxy_fcgi setenvif
sudo a2enconf php8.5-fpm
```

Create VirtualHost file `/etc/apache2/sites-available/xcrawler.conf`:

```apache
<VirtualHost *:80>
	ServerName xcrawler.net
	ServerAlias www.xcrawler.net
	DocumentRoot /home/joos/XCrawlerII/public

	<Directory /home/joos/XCrawlerII/public>
		AllowOverride All
		Require all granted
		Options Indexes FollowSymLinks
	</Directory>

	<FilesMatch \.php$>
		SetHandler "proxy:unix:/run/php/php8.5-fpm.sock|fcgi://localhost/"
	</FilesMatch>

	ErrorLog ${APACHE_LOG_DIR}/xcrawler-error.log
	CustomLog ${APACHE_LOG_DIR}/xcrawler-access.log combined
</VirtualHost>
```

Enable site and reload Apache:

```bash
sudo a2dissite 000-default.conf
sudo a2ensite xcrawler.conf
sudo apachectl configtest
sudo systemctl restart php8.5-fpm
sudo systemctl restart apache2
sudo systemctl enable apache2 php8.5-fpm
```

File ownership/permissions for Apache user (`www-data`):

```bash
sudo chown -R joos:www-data /home/joos/XCrawlerII
sudo find /home/joos/XCrawlerII -type d -exec chmod 755 {} \;
sudo find /home/joos/XCrawlerII -type f -exec chmod 644 {} \;
sudo chmod -R 775 /home/joos/XCrawlerII/storage /home/joos/XCrawlerII/bootstrap/cache
```

Frontend behavior with Apache:

- In production, Apache serves built Vite assets from `public/build`.
- Build assets on each release: `npm ci && npm run build`.
- Do not run `npm run dev` in production.

Optional HTTPS with Certbot:

```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d xcrawler.net -d www.xcrawler.net
```

### 7. System Dependencies

The project may require additional system libraries for certain extensions (e.g., `libmongoc` dependencies):

```bash
sudo apt install libcurl4-openssl-dev pkg-config libssl-dev -y
```

### 8. Storage & Permissions

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

### 9. Supervisor Configuration (for Horizon)

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

### 10. Crontab setup (for Scheduler)

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
