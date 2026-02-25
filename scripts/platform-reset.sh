#!/usr/bin/env sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
ROOT_DIR="$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)"

cd "$ROOT_DIR"

rebuild=false
seed=false
with_wings=false

usage() {
  cat <<'EOF'
Usage: scripts/platform-reset.sh [--rebuild] [--seed] [--with-wings]

Resets application data to a blank slate without rebuilding the full stack.

Options:
  --rebuild     Recreate containers and rebuild images before running reset steps.
  --seed        Run seeders on backend/orchestrator/legacy in addition to migrate:fresh.
  --with-wings  Also start Wings (testing profile) and clear Wings runtime state.
  --help        Show this help output.
EOF
}

while [ "$#" -gt 0 ]; do
  case "$1" in
    --rebuild)
      rebuild=true
      ;;
    --seed)
      seed=true
      ;;
    --with-wings)
      with_wings=true
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      usage
      exit 1
      ;;
  esac
  shift
done

start_required_services() {
  if [ "$rebuild" = "true" ]; then
    echo "Rebuilding and recreating platform containers..."
    if [ "$with_wings" = "true" ]; then
      docker compose --profile testing down --remove-orphans
      docker compose --profile testing up -d --build --force-recreate
    else
      docker compose down --remove-orphans
      docker compose up -d --build --force-recreate
    fi
  else
    echo "Restarting platform containers (no full rebuild)..."
    if [ "$with_wings" = "true" ]; then
      docker compose --profile testing stop
      docker compose --profile testing up -d
    else
      docker compose stop
      docker compose up -d
    fi
    if [ "$with_wings" = "true" ]; then
      docker compose --profile testing up -d pterodactyl-wings
    fi
  fi
}

wait_for_mysql_ready() {
  attempts=0
  until docker compose exec -T mysql mysqladmin ping -h 127.0.0.1 -uroot -psecret >/dev/null 2>&1; do
    attempts=$((attempts + 1))
    if [ "$attempts" -ge 30 ]; then
      echo "MySQL did not become ready in time." >&2
      exit 1
    fi
    sleep 2
  done
}

wait_for_php_service_port() {
  service="$1"
  port="$2"
  max_attempts="${3:-120}"
  attempts=0
  until docker compose exec -T "$service" php -r "exit(@fsockopen('127.0.0.1', $port) ? 0 : 1);" >/dev/null 2>&1; do
    attempts=$((attempts + 1))
    if [ "$attempts" -ge "$max_attempts" ]; then
      echo "$service did not become ready in time (waited $((max_attempts * 2))s)." >&2
      echo "Last 50 log lines from $service:" >&2
      docker compose logs --tail=50 "$service" >&2 || true
      exit 1
    fi
    sleep 2
  done
}

destroy_wings_server_state() {
  echo "Removing Wings-managed server runtime state..."
  managed_server_containers="$(docker ps -aq --filter label=Service=Pterodactyl --filter label=ContainerType=server_process 2>/dev/null || true)"
  if [ -n "$managed_server_containers" ]; then
    # shellcheck disable=SC2086
    docker rm -f $managed_server_containers >/dev/null 2>&1 || true
  fi
  rm -rf /tmp/pterodactyl/* /tmp/pterodactyl-logs/* /tmp/pterodactyl-tmp/* 2>/dev/null || true
}

ensure_pterodactyl_database() {
  echo "Ensuring shared MySQL has the pterodactyl schema and user..."
  docker compose exec -T mysql mysql -uroot -psecret <<'SQL'
CREATE DATABASE IF NOT EXISTS `pterodactyl` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'pterodactyl'@'%' IDENTIFIED WITH mysql_native_password BY 'secret';
ALTER USER 'pterodactyl'@'%' IDENTIFIED WITH mysql_native_password BY 'secret';
GRANT ALL PRIVILEGES ON `pterodactyl`.* TO 'pterodactyl'@'%';
FLUSH PRIVILEGES;
SQL
}

run_migrations() {
  echo "Running migrate:fresh across platform databases..."
  if [ "$seed" = "true" ]; then
    docker compose exec -T backend php artisan migrate:fresh --seed --force --no-interaction
    docker compose exec -T orchestrator php artisan migrate:fresh --seed --force --no-interaction
    docker compose exec -T legacy php artisan migrate:fresh --seed --force --no-interaction
  else
    docker compose exec -T backend php artisan migrate:fresh --force --no-interaction
    docker compose exec -T orchestrator php artisan migrate:fresh --force --no-interaction
    docker compose exec -T legacy php artisan migrate:fresh --force --no-interaction
  fi

  # Keep panel reset deterministic; provisioning below repopulates required local data.
  docker compose exec -T pterodactyl-panel php artisan migrate:fresh --force --no-interaction
}

pterodactyl_admin_email="${PTERODACTYL_ADMIN_EMAIL:-tom@intera.digital}"
pterodactyl_admin_username="${PTERODACTYL_ADMIN_USERNAME:-tom}"
pterodactyl_admin_first_name="${PTERODACTYL_ADMIN_FIRST_NAME:-Tom}"
pterodactyl_admin_last_name="${PTERODACTYL_ADMIN_LAST_NAME:-Kent}"
pterodactyl_admin_password="${PTERODACTYL_ADMIN_PASSWORD:-secret1234}"

seed_pterodactyl_admin_user() {
  echo "Seeding Pterodactyl admin user (${pterodactyl_admin_email})..."
  docker compose exec -T pterodactyl-panel php artisan p:user:make \
    --email="$pterodactyl_admin_email" \
    --username="$pterodactyl_admin_username" \
    --name-first="$pterodactyl_admin_first_name" \
    --name-last="$pterodactyl_admin_last_name" \
    --password="$pterodactyl_admin_password" \
    --admin=1 \
    --no-interaction
}

generate_pterodactyl_application_api_key() {
  echo "Generating Pterodactyl application API key for local provisioning..." >&2
  docker compose exec -T \
    -e PTERODACTYL_PROVISION_ADMIN_EMAIL="$pterodactyl_admin_email" \
    pterodactyl-panel php /dev/stdin < "$SCRIPT_DIR/pterodactyl-generate-api-key.php"
}

run_pterodactyl_provisioning() {
  seed_pterodactyl_admin_user
  pterodactyl_application_api_key="$(generate_pterodactyl_application_api_key)"

  echo "Running local Pterodactyl/Wings provisioning..."
  docker compose exec -T \
    -e PTERODACTYL_BASE_URL="http://pterodactyl-panel" \
    -e PTERODACTYL_APPLICATION_API_KEY="$pterodactyl_application_api_key" \
    orchestrator php artisan test:provision-local --no-interaction
}

start_required_services
wait_for_mysql_ready
wait_for_php_service_port backend 8000
wait_for_php_service_port orchestrator 8000
wait_for_php_service_port legacy 8080
wait_for_php_service_port pterodactyl-panel 80

if [ "$with_wings" = "true" ]; then
  destroy_wings_server_state
fi

ensure_pterodactyl_database
run_migrations
run_pterodactyl_provisioning

if [ "$rebuild" = "true" ]; then
  echo "Platform reset complete (with full container rebuild)."
else
  echo "Platform reset complete (without full container rebuild)."
fi
