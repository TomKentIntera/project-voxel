#!/usr/bin/env sh
set -eu

cd /var/www/html

# Create .env if it doesn't exist
if [ ! -f .env ]; then
  cat > .env <<'EOF'
APP_NAME="Intera Legacy"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://legacy.localhost

DB_CONNECTION=sqlite

LOG_CHANNEL=stderr

SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

LOCATION_TESTING=true
EOF
fi

# Create SQLite database file
mkdir -p database
if [ ! -f database/database.sqlite ]; then
  touch database/database.sqlite
fi

# Create storage directories Laravel expects
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/testing
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Create an empty locations.json so the homepage doesn't error
if [ ! -f storage/app/locations.json ]; then
  echo '{}' > storage/app/locations.json
fi

# Install dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader 2>&1

# Generate app key if missing
if ! grep -q "^APP_KEY=base64:" .env; then
  php artisan key:generate --force --no-interaction
fi

# Run migrations (best-effort, homepage doesn't strictly need them)
php artisan migrate --force --no-interaction 2>&1 || true

# Clear and cache config/views for faster rendering
php artisan config:clear
php artisan view:clear

echo "==> Legacy app starting on port 8080"
exec php artisan serve --host=0.0.0.0 --port=8080

