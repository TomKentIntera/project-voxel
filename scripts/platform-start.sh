#!/usr/bin/env sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
ROOT_DIR="$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)"

cd "$ROOT_DIR"

docker compose up -d

if [ -x "$SCRIPT_DIR/event-bus-terraform.sh" ]; then
  "$SCRIPT_DIR/event-bus-terraform.sh" local apply --auto-approve
fi

echo "Platform stack started."
echo "Frontend:  http://store.localhost"
echo "API:       http://api.localhost"
echo "Orchestrator: http://orchestrator.localhost"
echo "Storybook: http://storybook.localhost"
echo "Panel:     http://panel.localhost"
