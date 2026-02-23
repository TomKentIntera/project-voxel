#!/usr/bin/env sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
ROOT_DIR="$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)"

cd "$ROOT_DIR"

with_wings=false
skip_provision=false
force_provision=false

usage() {
  cat <<'EOF'
Usage: scripts/platform-start.sh [--with-wings] [--skip-provision] [--force-provision]

Options:
  --with-wings      Start testing-only Wings profile.
  --skip-provision  Skip LocalStack Terraform provisioning.
  --force-provision Always run LocalStack Terraform provisioning.
  --help            Show this help output.
EOF
}

while [ "$#" -gt 0 ]; do
  case "$1" in
    --with-wings)
      with_wings=true
      ;;
    --skip-provision)
      skip_provision=true
      ;;
    --force-provision)
      force_provision=true
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

if [ "$skip_provision" = "true" ] && [ "$force_provision" = "true" ]; then
  echo "Cannot use --skip-provision and --force-provision together." >&2
  exit 1
fi

if [ "$with_wings" = "true" ]; then
  docker compose --profile testing up -d
else
  docker compose up -d
fi

is_event_bus_provisioned() {
  topic_name="${EVENT_BUS_TOPIC_NAME:-server-orders}"
  queue_name="${EVENT_BUS_QUEUE_NAME:-server-orders-orchestrator}"
  lifecycle_queue_name="${EVENT_BUS_LIFECYCLE_QUEUE_NAME:-server-lifecycle-backend}"
  aws_region="${AWS_DEFAULT_REGION:-us-east-1}"
  topic_arn="arn:aws:sns:${aws_region}:000000000000:${topic_name}"

  if ! docker compose exec -T localstack awslocal sns get-topic-attributes --topic-arn "$topic_arn" >/dev/null 2>&1; then
    return 1
  fi

  if ! docker compose exec -T localstack awslocal sqs get-queue-url --queue-name "$queue_name" >/dev/null 2>&1; then
    return 1
  fi

  if ! docker compose exec -T localstack awslocal sqs get-queue-url --queue-name "$lifecycle_queue_name" >/dev/null 2>&1; then
    return 1
  fi

  return 0
}

if [ ! -x "$SCRIPT_DIR/event-bus-terraform.sh" ]; then
  :
elif [ "$skip_provision" = "true" ]; then
  echo "Skipping LocalStack event bus provisioning (--skip-provision)."
elif [ "$force_provision" = "true" ]; then
  "$SCRIPT_DIR/event-bus-terraform.sh" local apply --auto-approve
elif is_event_bus_provisioned; then
  echo "LocalStack event bus already provisioned. Skipping Terraform apply."
else
  "$SCRIPT_DIR/event-bus-terraform.sh" local apply --auto-approve
fi

echo "Platform stack started."
echo "Frontend:  http://store.localhost"
echo "API:       http://api.localhost"
echo "Orchestrator: http://orchestrator.localhost"
echo "Storybook: http://storybook.localhost"
echo "Panel:     http://panel.localhost"
echo "MinIO API: http://127.0.0.1:9000"
echo "MinIO UI:  http://127.0.0.1:9001"
if [ "$with_wings" = "true" ]; then
  echo "Wings API: http://127.0.0.1:8080"
  echo "Wings SFTP: sftp://127.0.0.1:2022"
fi
