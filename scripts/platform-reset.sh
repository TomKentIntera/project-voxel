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

Options:
  --rebuild     Recreate containers and rebuild images before starting.
  --seed        Run database seeders after migrate:fresh.
  --with-wings  Start testing-only Wings profile.
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
      echo "Unknown option: $1"
      usage
      exit 1
      ;;
  esac
  shift
done

compose_cmd="docker compose"
if [ "$with_wings" = "true" ]; then
  compose_cmd="docker compose --profile testing"
fi

if [ "$rebuild" = "true" ]; then
  $compose_cmd down --remove-orphans
  $compose_cmd up -d --build --force-recreate
else
  $compose_cmd stop
  $compose_cmd up -d
fi

ensure_pterodactyl_database() {
  echo "Ensuring shared MySQL has the pterodactyl schema..."
  docker compose exec -T mysql mysql -uroot -psecret <<'SQL'
CREATE DATABASE IF NOT EXISTS `pterodactyl` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'pterodactyl'@'%' IDENTIFIED WITH mysql_native_password BY 'secret';
ALTER USER 'pterodactyl'@'%' IDENTIFIED WITH mysql_native_password BY 'secret';
GRANT ALL PRIVILEGES ON `pterodactyl`.* TO 'pterodactyl'@'%';
FLUSH PRIVILEGES;
SQL
}

ensure_pterodactyl_database

if [ -x "$SCRIPT_DIR/event-bus-terraform.sh" ]; then
  "$SCRIPT_DIR/event-bus-terraform.sh" local apply --auto-approve
fi

wait_for_service() {
  service="$1"
  port="$2"
  echo "Waiting for $service to become ready..."
  elapsed=0
  until docker compose exec -T "$service" php -r "exit(@fsockopen('127.0.0.1', $port) ? 0 : 1);" 2>/dev/null; do
    elapsed=$((elapsed + 2))
    if [ "$elapsed" -ge 10 ]; then
      echo "$service did not become ready within 10 seconds – check 'docker compose logs $service'." >&2
      exit 1
    fi
    sleep 2
  done
}

wait_for_service backend 8000
wait_for_service orchestrator 8000
wait_for_service legacy 8080

if [ "$seed" = "true" ]; then
  docker compose exec -T backend php artisan migrate:fresh --seed --force --no-interaction
  docker compose exec -T orchestrator php artisan migrate --force --no-interaction
  docker compose exec -T legacy php artisan migrate:fresh --seed --force --no-interaction
else
  docker compose exec -T backend php artisan migrate:fresh --force --no-interaction
  docker compose exec -T orchestrator php artisan migrate --force --no-interaction
  docker compose exec -T legacy php artisan migrate:fresh --force --no-interaction
fi

echo "Platform stack reset complete."
