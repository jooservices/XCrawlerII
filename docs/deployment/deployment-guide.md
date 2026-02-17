# Deployment Guide (Fresh Server)

This guide provides a step-by-step "Zero to Hero" walkthrough for deploying XCrawlerII on a fresh Ubuntu 24.04 LTS (or 22.04) server.

## 1. System Preparation & Prerequisites

First, ensure your system is up to date and has the basic tools installed.

```bash
# Update package lists and upgrade existing packages
sudo apt update && sudo apt upgrade -y

# Install essential tools
sudo apt install -y git curl zip unzip software-properties-common
```

## 2. Service Installation

We will install the **LATEST** stable versions of all services using their official repositories. Do not rely on default Ubuntu repositories as they are often outdated.

### 2.1 Web Server: Nginx (Latest Stable)

We use the official Nginx PPA to ensure we have the latest stable version.

```bash
sudo add-apt-repository ppa:nginx/stable -y
sudo apt update
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

### 2.2 Database: MySQL 8.0+

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
# Answer Y to most questions. Choose a strong root password.
```

**Create Database & User:**
```bash
sudo mysql -u root -p
```
```sql
-- Inside MySQL shell
CREATE DATABASE xcrawlerii CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'xcrawlerii'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON xcrawlerii.* TO 'xcrawlerii'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2.3 Cache & Queues: Redis (Latest Official)

We install from `packages.redis.io` to get the latest 7.x/8.x version.

```bash
# Import GPG Key
curl -fsSL https://packages.redis.io/gpg | sudo gpg --dearmor -o /usr/share/keyrings/redis-archive-keyring.gpg

# Add Repository
echo "deb [signed-by=/usr/share/keyrings/redis-archive-keyring.gpg] https://packages.redis.io/deb $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/redis.list

# Install Latest Redis
sudo apt update
sudo apt install -y redis
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

### 2.4 NoSQL Database: MongoDB 8.0 (Latest)

We install from `repo.mongodb.org` to get the latest MongoDB 8.0 Community Edition.

```bash
# Import Public Key
curl -fsSL https://www.mongodb.org/static/pgp/server-8.0.asc | \
   sudo gpg -o /usr/share/keyrings/mongodb-server-8.0.gpg \
   --dearmor

# Add Repository
echo "deb [ arch=amd64,arm64 signed-by=/usr/share/keyrings/mongodb-server-8.0.gpg ] https://repo.mongodb.org/apt/ubuntu jammy/mongodb-org/8.0 multiverse" | sudo tee /etc/apt/sources.list.d/mongodb-org-8.0.list

# Install MongoDB 8.0
sudo apt update
sudo apt install -y mongodb-org

# Start Service
sudo systemctl start mongod
sudo systemctl enable mongod
```

### 2.5 Search Engine: Elasticsearch 8.x (Latest)

```bash
# Download and install public signing key
wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | sudo gpg --dearmor -o /usr/share/keyrings/elasticsearch-keyring.gpg

# Install from APT repository
sudo apt-get install -y apt-transport-https
echo "deb [signed-by=/usr/share/keyrings/elasticsearch-keyring.gpg] https://artifacts.elastic.co/packages/8.x/apt stable main" | sudo tee /etc/apt/sources.list.d/elastic-8.x.list

# Install
sudo apt-get update && sudo apt-get install -y elasticsearch

# Start
sudo systemctl enable elasticsearch
sudo systemctl start elasticsearch
```

**Verify Services:**
-   Nginx: `nginx -v`
-   MySQL: `mysql --version`
-   Redis: `redis-server --version`
-   Mongo: `mongod --version`
-   Elastic: `curl localhost:9200`


## 3. PHP 8.5 (Mandatory)

This project requires **PHP 8.5**. We use the `ondrej/php` PPA to install this specific version and its extensions.

### Understanding Package Managers: APT vs PECL

-   **APT (Advanced Package Tool)**: The default package manager for Ubuntu. It is strictly version-controlled, stable, and easy to update (`apt upgrade`). **Recommendation**: Always try to use APT first.
-   **PECL (PHP Extension Community Library)**: A repository for PHP extensions. Installing via PECL compiles the extension from source. It gives you the latest versions but requires compiler tools (`build-essential`) and manual updates.

**Step 1: Add PHP Repository (Ondrej PPA)**
Ubuntu's default repo might have older PHP. We use the main tailored PPA.

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
```

**Step 2: Install PHP 8.5**
*Ensure you install version 8.5. If 8.5 is in RC, it is still required for this codebase.*

```bash
sudo apt install -y php8.5-fpm php8.5-cli php8.5-common \
    php8.5-curl php8.5-mbstring php8.5-xml php8.5-zip \
    php8.5-bcmath php8.5-intl php8.5-gd php8.5-mysql \
    php8.5-dev php-pear
```

**Step 3: Install MongoDB & Redis Extensions**
For latest features, we can use PECL if APT packages (`php8.5-mongodb`, `php8.5-redis`) are not yet available for the bleeding edge PHP version.

```bash
# Install specific extensions for 8.5
sudo apt install -y php8.5-mongodb php8.5-redis
# OR via PECL if apt is missing them:
# sudo pecl install redis mongodb
```

## 4. Application Setup

### 4.1 Clone Repository
 
 ```bash
 cd /var/www
 sudo git clone https://github.com/joosectors/XCrawlerII.git xcrawler
 # Fix Permissions for deployment user (assuming 'ubuntu' or your user)
 sudo chown -R $USER:$USER xcrawler
 cd xcrawler
 ```

### 4.2 Application Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies & Build Assets
npm ci
npm run build
```

### 4.3 Configuration

```bash
cp .env.example .env
nano .env
```

**Update `.env` values:**
-   `APP_URL=https://your-domain.com`
-   `DB_DATABASE=xcrawlerii`, `DB_USERNAME=xcrawlerii`, `DB_PASSWORD=...`
-   `REDIS_HOST=127.0.0.1`
-   `MONGODB_HOST=127.0.0.1`
-   `ELASTICSEARCH_HOST=http://127.0.0.1:9200`

```bash
# Generate Key
php artisan key:generate

# Run Migrations
php artisan migrate --force
```

### 4.4 Directory Permissions

Nginx (`www-data`) needs write access to storage.

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 5. Web Server Configuration (Nginx)

Create a new configuration file:

```bash
sudo nano /etc/nginx/sites-available/xcrawler
```

**Content:**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/xcrawler/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.html index.htm index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock; # CHECK THIS PATH matches your version
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Enable Site:**
```bash
sudo ln -s /etc/nginx/sites-available/xcrawler /etc/nginx/sites-enabled/
sudo unlink /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

## 6. Background Workers (Supervisor)

Laravel needs a process monitor to keep queue workers running.

```bash
sudo apt install -y supervisor
sudo nano /etc/supervisor/conf.d/xcrawler-horizon.conf
```

**Content:**
```ini
[program:xcrawler-horizon]
process_name=%(program_name)s
command=php /var/www/xcrawler/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/xcrawler/storage/logs/horizon.log
stopwaitsecs=3600
```

**Start Supervisor:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start xcrawler-horizon
```

## 7. Scheduler (Cron)

Run the scheduler every minute.

```bash
sudo crontab -u www-data -e
```

**Add Line:**
```cron
* * * * * cd /var/www/xcrawler && php artisan schedule:run >> /dev/null 2>&1
```

## 8. Final Checks

1.  Visit `https://your-domain.com`.
2.  Check Queue Status: `php artisan horizon:status`.
3.  Check Logs: `tail -f storage/logs/laravel.log`.
