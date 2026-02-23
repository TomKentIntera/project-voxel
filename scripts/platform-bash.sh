#!/usr/bin/env sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
ROOT_DIR="$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)"

cd "$ROOT_DIR"

usage() {
  cat <<'EOF'
Usage: scripts/platform-bash.sh <service>

Examples:
  scripts/platform-bash.sh backend
  scripts/platform-bash.sh orchestrator
EOF
}

if [ "${1:-}" = "--help" ] || [ "${1:-}" = "-h" ]; then
  usage
  exit 0
fi

if [ "$#" -ne 1 ]; then
  usage
  exit 1
fi

service="$1"

if ! docker compose ps --services --filter status=running | grep -Fx "$service" >/dev/null 2>&1; then
  echo "Service '$service' is not running. Start it first with scripts/platform-start.sh." >&2
  exit 1
fi

if ! docker compose exec "$service" sh -lc "command -v bash >/dev/null 2>&1"; then
  echo "Service '$service' does not have bash installed." >&2
  exit 1
fi

exec docker compose exec "$service" bash

