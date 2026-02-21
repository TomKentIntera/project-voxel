#!/usr/bin/env sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
ROOT_DIR="$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)"
TF_DIR="$ROOT_DIR/infrastructure/terraform/event-bus"

usage() {
  cat <<'EOF'
Usage: scripts/event-bus-terraform.sh <local|aws> <init|plan|apply|destroy|output|output-env> [terraform args...]

Examples:
  scripts/event-bus-terraform.sh local apply --auto-approve
  scripts/event-bus-terraform.sh local output-env
  scripts/event-bus-terraform.sh aws plan

Environment overrides:
  AWS_DEFAULT_REGION                     (default: us-east-1)
  EVENT_BUS_TOPIC_NAME                   (default: server-orders)
  EVENT_BUS_QUEUE_NAME                   (default: server-orders-orchestrator)
  EVENT_BUS_DLQ_NAME                     (default: server-orders-orchestrator-dlq)
  EVENT_BUS_MAX_RECEIVE_COUNT            (default: 5)
  LOCALSTACK_ENDPOINT                    (default: http://localhost:4566)
EOF
}

if [ "${1:-}" = "--help" ] || [ "${1:-}" = "-h" ]; then
  usage
  exit 0
fi

if [ "$#" -lt 2 ]; then
  usage
  exit 1
fi

if ! command -v terraform >/dev/null 2>&1; then
  echo "terraform is required but was not found in PATH." >&2
  exit 127
fi

target="$1"
action="$2"
shift 2

case "$target" in
  local|aws)
    ;;
  *)
    echo "Invalid target: $target" >&2
    usage
    exit 1
    ;;
esac

case "$action" in
  init|plan|apply|destroy|output|output-env)
    ;;
  *)
    echo "Invalid action: $action" >&2
    usage
    exit 1
    ;;
esac

export TF_VAR_aws_region="${AWS_DEFAULT_REGION:-us-east-1}"
export TF_VAR_topic_name="${EVENT_BUS_TOPIC_NAME:-server-orders}"
export TF_VAR_queue_name="${EVENT_BUS_QUEUE_NAME:-server-orders-orchestrator}"
export TF_VAR_dead_letter_queue_name="${EVENT_BUS_DLQ_NAME:-server-orders-orchestrator-dlq}"
export TF_VAR_max_receive_count="${EVENT_BUS_MAX_RECEIVE_COUNT:-5}"

if [ "$target" = "local" ]; then
  export TF_VAR_use_localstack=true
  export TF_VAR_localstack_endpoint="${LOCALSTACK_ENDPOINT:-http://localhost:4566}"
  export TF_VAR_localstack_access_key="${AWS_ACCESS_KEY_ID:-test}"
  export TF_VAR_localstack_secret_key="${AWS_SECRET_ACCESS_KEY:-test}"

  if command -v curl >/dev/null 2>&1 && [ "$action" != "output" ] && [ "$action" != "output-env" ]; then
    attempts=0
    until curl -sf "${TF_VAR_localstack_endpoint}/_localstack/health" >/dev/null 2>&1; do
      attempts=$((attempts + 1))
      if [ "$attempts" -ge 30 ]; then
        echo "LocalStack did not become ready at ${TF_VAR_localstack_endpoint}." >&2
        exit 1
      fi
      sleep 2
    done
  fi
else
  export TF_VAR_use_localstack=false
fi

run_tf() {
  terraform -chdir="$TF_DIR" "$@"
}

case "$action" in
  init)
    run_tf init "$@"
    ;;
  plan)
    run_tf init -upgrade
    run_tf plan "$@"
    ;;
  apply)
    run_tf init -upgrade
    run_tf apply "$@"
    ;;
  destroy)
    run_tf init -upgrade
    run_tf destroy "$@"
    ;;
  output)
    run_tf output "$@"
    ;;
  output-env)
    topic_arn="$(run_tf output -raw topic_arn)"
    queue_url="$(run_tf output -raw queue_url)"
    cat <<EOF
export EVENT_BUS_SERVER_ORDERS_TOPIC_ARN="$topic_arn"
export EVENT_BUS_SERVER_ORDERS_QUEUE_URL="$queue_url"
EOF
    ;;
esac
