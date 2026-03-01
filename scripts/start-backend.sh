#!/usr/bin/env sh
set -eu

role="${1:-web}"

cd /var/www/html

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

upsert_env_var() {
  key="$1"
  value="$2"

  if [ ! -f .env ]; then
    return
  fi

  escaped_value=$(printf '%s' "$value" | sed 's/[\/&]/\\&/g')

  if grep -q "^${key}=" .env; then
    sed -i "s/^${key}=.*/${key}=${escaped_value}/" .env
  else
    printf '\n%s=%s\n' "$key" "$value" >> .env
  fi
}

for key in \
  LOCATIONS_CACHE_DISK \
  LOCATIONS_CACHE_PATH \
  LOCATIONS_CACHE_TTL_SECONDS \
  LOCATIONS_CACHE_DRIVER \
  LOCATIONS_CACHE_LOCAL_ROOT \
  LOCATIONS_CACHE_ROOT \
  LOCATIONS_CACHE_AWS_ACCESS_KEY_ID \
  LOCATIONS_CACHE_AWS_SECRET_ACCESS_KEY \
  LOCATIONS_CACHE_AWS_DEFAULT_REGION \
  LOCATIONS_CACHE_AWS_BUCKET \
  LOCATIONS_CACHE_AWS_ENDPOINT \
  LOCATIONS_CACHE_AWS_USE_PATH_STYLE_ENDPOINT
do
  eval "value=\${$key-}"
  if [ -n "${value}" ]; then
    upsert_env_var "$key" "$value"
  fi
done

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
  # Ensure schema is fully migrated before queue worker touches database-backed cache/queues.
  until php artisan migrate --force --no-interaction >/dev/null 2>&1; do
    sleep 2
  done

  exec php artisan queue:work --sleep=1 --tries=3 --timeout=90 --verbose
fi

if [ "$role" = "event-consumer" ]; then
  # Ensure schema is fully migrated before starting event processing.
  until php artisan migrate --force --no-interaction >/dev/null 2>&1; do
    sleep 2
  done

  exec php artisan events:consume-server-lifecycle --max-messages=10 --wait=20 --sleep=1
fi

echo "Unknown backend role: $role"
exit 1
