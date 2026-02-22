#!/usr/bin/env sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
ROOT_DIR="$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)"

cd "$ROOT_DIR"

with_wings=false

usage() {
  cat <<'EOF'
Usage: scripts/platform-start.sh [--with-wings]

Options:
  --with-wings  Start testing-only Wings profile.
  --help        Show this help output.
EOF
}

while [ "$#" -gt 0 ]; do
  case "$1" in
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

if [ "$with_wings" = "true" ]; then
  docker compose --profile testing up -d
else
  docker compose up -d
fi

if [ -x "$SCRIPT_DIR/event-bus-terraform.sh" ]; then
  "$SCRIPT_DIR/event-bus-terraform.sh" local apply --auto-approve
fi

echo "Platform stack started."
echo "Frontend:  http://store.localhost"
echo "API:       http://api.localhost"
echo "Orchestrator: http://orchestrator.localhost"
echo "Storybook: http://storybook.localhost"
echo "Panel:     http://panel.localhost"
if [ "$with_wings" = "true" ]; then
  echo "Wings API: http://127.0.0.1:8080"
  echo "Wings SFTP: sftp://127.0.0.1:2022"
fi
