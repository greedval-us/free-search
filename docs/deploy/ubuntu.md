# Ubuntu Deployment Guide

This project can be deployed on a single Ubuntu server with:

- `nginx`
- `php-fpm`
- `mysql` or `mariadb`
- `systemd` queue worker
- optional `redis`

## 1. Server Packages

Example for Ubuntu 24.04:

```bash
sudo apt update
sudo apt install -y \
  nginx \
  git \
  unzip \
  curl \
  mysql-client \
  php8.3-cli \
  php8.3-fpm \
  php8.3-mysql \
  php8.3-sqlite3 \
  php8.3-curl \
  php8.3-mbstring \
  php8.3-xml \
  php8.3-zip \
  php8.3-bcmath \
  php8.3-intl \
  php8.3-gd \
  php8.3-redis \
  composer
```

Install Node.js 22 if you plan to build assets on the server.

If you build assets in CI and upload the ready `public/build` directory with the release, Node.js is not required on the server.

After PHP is installed, validate runtime requirements:

```bash
composer check-platform-reqs
```

## 2. Application Layout

Recommended path:

```bash
/var/www/free-search
```

Example:

```bash
sudo mkdir -p /var/www/free-search
sudo chown -R $USER:$USER /var/www/free-search
git clone <your-repository-url> /var/www/free-search
cd /var/www/free-search
```

## 3. Production Environment

Create `.env` from `.env.example` and set at least:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=free_search
DB_USERNAME=free_search
DB_PASSWORD=change-me

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=info
```

Also configure the external integrations you actually use:

- `TELEGRAM_API_ID`
- `TELEGRAM_API_HASH`
- `YOUTUBE_DATA_API_KEY`
- `OSINT_NEWSAPI_KEY`
- `BLUESKY_IDENTIFIER`
- `BLUESKY_APP_PASSWORD`
- `MASTODON_API_TOKEN`

For MoonShine admin in production set:

```dotenv
MOONSHINE_DOMAIN=admin.your-domain.example
MOONSHINE_ROUTE_PREFIX=control-room
MOONSHINE_ENFORCE_IP_ALLOWLIST=true
MOONSHINE_ALLOWED_IPS=203.0.113.10
MOONSHINE_LOGIN_MAX_ATTEMPTS=3
MOONSHINE_LOGIN_DECAY_SECONDS=60
```

## 4. Install and Bootstrap

Run:

```bash
cd /var/www/free-search
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan storage:link
```

The default production stack in this repository expects database-backed tables for:

- cache
- sessions
- jobs
- failed jobs

Those tables are already included in the tracked migrations, so `php artisan migrate --force` is enough on a fresh Ubuntu server.

If you build assets on the server:

```bash
php artisan wayfinder:generate --with-form
npm ci
npm run build
```

Then cache Laravel metadata:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 5. Writable Directories

The web user must be able to write to:

- `storage/`
- `bootstrap/cache/`

Example:

```bash
sudo chown -R www-data:www-data /var/www/free-search/storage /var/www/free-search/bootstrap/cache
sudo find /var/www/free-search/storage -type d -exec chmod 775 {} \;
sudo find /var/www/free-search/bootstrap/cache -type d -exec chmod 775 {} \;
```

## 6. Nginx

Use `docs/deploy/examples/nginx/free-search.conf.example` as a base.

Important points:

- document root must be `/var/www/free-search/public`
- PHP must point to your `php-fpm` socket
- hidden files must stay inaccessible
- add TLS with `certbot` or your reverse proxy before going live

## 7. Queue Worker

This project uses database-backed queues by default, so a worker should run permanently in production.

Use `docs/deploy/examples/systemd/free-search-queue.service.example` as a base, then:

```bash
sudo cp docs/deploy/examples/systemd/free-search-queue.service.example /etc/systemd/system/free-search-queue.service
sudo systemctl daemon-reload
sudo systemctl enable --now free-search-queue
sudo systemctl status free-search-queue
```

After each deploy, restart the worker gracefully:

```bash
php artisan queue:restart
sudo systemctl restart free-search-queue
```

## 8. Scheduler

Set one cron entry:

```bash
* * * * * cd /var/www/free-search && php artisan schedule:run >> /dev/null 2>&1
```

The current project does not define scheduled tasks yet, but keeping the scheduler enabled is a safe production baseline.

## 9. Deploy Command

Use `scripts/deploy/post-deploy.sh` after each release:

```bash
cd /var/www/free-search
bash scripts/deploy/post-deploy.sh
```

## 10. Health Checks After Deploy

Run:

```bash
php artisan about
php artisan migrate:status
php artisan config:show app
```

Then manually verify:

- main app loads
- login and register pages load
- MoonShine admin is reachable only from allowed IPs
- one real search request works
- queue worker is active
- `storage/logs/laravel.log` stays clean

## 11. Release Flow

Recommended order on Ubuntu:

1. Put the app in maintenance mode if the release is risky.
2. Pull the new code.
3. Run `bash scripts/deploy/post-deploy.sh`.
4. Restart `php-fpm` only if PHP packages changed.
5. Restart the queue worker.
6. Disable maintenance mode.

Example:

```bash
php artisan down
git pull origin main
bash scripts/deploy/post-deploy.sh
sudo systemctl restart free-search-queue
php artisan up
```

## 12. Notes

- Build artifacts can be produced on CI instead of on the server.
- If frontend routes or controller signatures changed, regenerate Wayfinder before `npm run build`.
- If you switch to Redis for cache, session, or queue, update `.env` and keep Redis running.
- On Ubuntu you should not hit the Windows-specific Blade and Vite file locking issues seen locally.
