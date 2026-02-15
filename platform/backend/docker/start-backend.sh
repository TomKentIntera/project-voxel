#!/usr/bin/env sh
set -eu

role="${1:-web}"

cd /var/www/html

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

mkdir -p database
if [ ! -f database/database.sqlite ]; then
  touch database/database.sqlite
fi

composer install --no-interaction --prefer-dist --optimize-autoloader

if [ -f .env ] && ! grep -q "^APP_KEY=base64:" .env; then
  php artisan key:generate --force --no-interaction
fi

if [ "$role" = "web" ]; then
  php artisan migrate --force --no-interaction
  exec php artisan serve --host=0.0.0.0 --port=8000
fi

if [ "$role" = "worker" ]; then
  # Wait for the migrations table to exist before starting queue processing.
  until php artisan migrate:status >/dev/null 2>&1; do
    sleep 2
  done

  exec php artisan queue:work --sleep=1 --tries=3 --timeout=90 --verbose
fi

echo "Unknown backend role: $role"
exit 1
