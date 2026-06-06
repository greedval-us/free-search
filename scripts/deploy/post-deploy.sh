#!/usr/bin/env bash

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

cd "$APP_DIR"

echo "[deploy] Installing PHP dependencies"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

if command -v npm >/dev/null 2>&1; then
  if php artisan list --raw | grep -q '^wayfinder:generate$'; then
    echo "[deploy] Generating Wayfinder routes"
    php artisan wayfinder:generate --with-form
  fi

  echo "[deploy] Installing Node dependencies"
  npm ci

  echo "[deploy] Building frontend assets"
  npm run build
else
  echo "[deploy] npm not found, skipping asset build"
fi

echo "[deploy] Running database migrations"
php artisan migrate --force

echo "[deploy] Ensuring storage symlink"
php artisan storage:link || true

echo "[deploy] Clearing stale caches"
php artisan optimize:clear

echo "[deploy] Rebuilding production caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[deploy] Signalling queue workers to restart"
php artisan queue:restart

echo "[deploy] Done"
