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

start_infra() {
  docker compose up -d mysql localstack minio minio-create-bucket
}

start_stack() {
  if [ "$with_wings" = "true" ]; then
    docker compose --profile testing up -d
  else
    docker compose up -d
  fi
}

sync_wings_config_from_panel_node_if_available() {
  if [ "$with_wings" != "true" ]; then
    return 0
  fi

  config_path="$ROOT_DIR/docker/wings/config.yml"
  if [ ! -f "$config_path" ]; then
    echo "Wings config file not found at $config_path" >&2
    return 0
  fi

  node_credentials="$(docker compose exec -T mysql mysql -N -B -uroot -psecret pterodactyl -e "SELECT uuid, daemon_token_id, daemon_token FROM nodes WHERE fqdn='pterodactyl-wings' ORDER BY id ASC LIMIT 1;" 2>/dev/null || true)"

  if [ -z "$node_credentials" ]; then
    echo "No pterodactyl-wings node credentials found yet; leaving existing Wings config as-is."
    return 0
  fi

  wings_uuid="$(printf '%s\n' "$node_credentials" | awk -F '\t' '{print $1}')"
  wings_token_id="$(printf '%s\n' "$node_credentials" | awk -F '\t' '{print $2}')"
  wings_token="$(printf '%s\n' "$node_credentials" | awk -F '\t' '{print $3}')"

  if [ -z "$wings_uuid" ] || [ -z "$wings_token_id" ] || [ -z "$wings_token" ]; then
    echo "Incomplete Wings node credentials found; leaving existing Wings config as-is." >&2
    return 0
  fi

  temp_config="$(mktemp)"
  awk \
    -v wings_uuid="$wings_uuid" \
    -v wings_token_id="$wings_token_id" \
    -v wings_token="$wings_token" '
      /^uuid:/ { print "uuid: " wings_uuid; next }
      /^token_id:/ { print "token_id: " wings_token_id; next }
      /^token:/ { print "token: " wings_token; next }
      { print }
    ' "$config_path" > "$temp_config"

  mv "$temp_config" "$config_path"
  docker compose --profile testing up -d --force-recreate pterodactyl-wings
  echo "Updated docker/wings/config.yml and restarted pterodactyl-wings with current Panel credentials."
}

start_infra

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

wait_for_localstack_ready() {
  attempts=0
  until docker compose exec -T localstack sh -lc "curl -sf http://localhost:4566/_localstack/health >/dev/null" >/dev/null 2>&1; do
    attempts=$((attempts + 1))
    if [ "$attempts" -ge 30 ]; then
      echo "LocalStack did not become ready in time." >&2
      exit 1
    fi
    sleep 2
  done
}

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

wait_for_localstack_event_bus_restore() {
  attempts=0
  while [ "$attempts" -lt 20 ]; do
    if is_event_bus_provisioned; then
      return 0
    fi

    attempts=$((attempts + 1))
    sleep 2
  done

  return 1
}

wait_for_mysql_ready
wait_for_localstack_ready
ensure_pterodactyl_database

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
elif wait_for_localstack_event_bus_restore; then
  echo "LocalStack event bus already provisioned. Skipping Terraform apply."
else
  "$SCRIPT_DIR/event-bus-terraform.sh" local apply --auto-approve
fi

start_stack
sync_wings_config_from_panel_node_if_available

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
