#!/usr/bin/env sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
ROOT_DIR="$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)"

cd "$ROOT_DIR"

rebuild=false
seed=false

usage() {
  cat <<'EOF'
Usage: scripts/platform-reset.sh [--rebuild] [--seed]

Options:
  --rebuild   Recreate containers and rebuild images before starting.
  --seed      Run database seeders after migrate:fresh.
  --help      Show this help output.
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

if [ "$rebuild" = "true" ]; then
  docker compose down --remove-orphans
  docker compose up -d --build --force-recreate
else
  docker compose stop
  docker compose up -d
fi

echo "Waiting for backend to become ready..."
until docker compose exec -T backend php artisan about >/dev/null 2>&1; do
  sleep 2
done

if [ "$seed" = "true" ]; then
  docker compose exec -T backend php artisan migrate:fresh --seed --force --no-interaction
else
  docker compose exec -T backend php artisan migrate:fresh --force --no-interaction
fi

echo "Platform stack reset complete."
