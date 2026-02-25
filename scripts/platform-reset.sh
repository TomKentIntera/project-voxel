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

destroy_wings_containers() {
  echo "Destroying any existing Wings containers..."
  docker compose --profile testing stop pterodactyl-wings >/dev/null 2>&1 || true
  docker compose --profile testing rm -f pterodactyl-wings >/dev/null 2>&1 || true
}

destroy_wings_server_state() {
  echo "Destroying Wings-managed server containers and runtime data..."

  managed_server_containers="$(docker ps -aq --filter label=Service=Pterodactyl --filter label=ContainerType=server_process 2>/dev/null || true)"
  if [ -n "$managed_server_containers" ]; then
    # shellcheck disable=SC2086
    docker rm -f $managed_server_containers >/dev/null 2>&1 || true
  fi

  rm -rf /tmp/pterodactyl/* /tmp/pterodactyl-logs/* /tmp/pterodactyl-tmp/* 2>/dev/null || true
}

destroy_wings_containers
destroy_wings_server_state

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
  echo "Generating Pterodactyl application API key for local orchestrator provisioning..." >&2

  docker compose exec -T \
    -e PTERODACTYL_PROVISION_ADMIN_EMAIL="$pterodactyl_admin_email" \
    pterodactyl-panel php <<'PHP'
<?php
require '/app/vendor/autoload.php';

$app = require '/app/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$adminEmail = trim((string) getenv('PTERODACTYL_PROVISION_ADMIN_EMAIL'));
if ($adminEmail === '') {
    fwrite(STDERR, 'Missing panel admin email for API key generation.' . PHP_EOL);
    exit(1);
}

$user = Pterodactyl\Models\User::query()
    ->where('email', $adminEmail)
    ->first();

if ($user === null) {
    fwrite(STDERR, sprintf('Could not find panel admin user [%s] for API key generation.', $adminEmail) . PHP_EOL);
    exit(1);
}

$memo = 'Local orchestrator provisioning key';
$key = Pterodactyl\Models\ApiKey::query()
    ->where('user_id', $user->id)
    ->where('key_type', Pterodactyl\Models\ApiKey::TYPE_APPLICATION)
    ->where('memo', $memo)
    ->latest('id')
    ->first();

if ($key === null) {
    $permissions = [
        'r_servers' => 3,
        'r_nodes' => 3,
        'r_allocations' => 3,
        'r_users' => 3,
        'r_locations' => 3,
        'r_nests' => 3,
        'r_eggs' => 3,
        'r_database_hosts' => 3,
        'r_server_databases' => 3,
    ];

    $key = $app->make(Pterodactyl\Services\Api\KeyCreationService::class)
        ->setKeyType(Pterodactyl\Models\ApiKey::TYPE_APPLICATION)
        ->handle([
            'memo' => $memo,
            'user_id' => $user->id,
            'allowed_ips' => [],
        ], $permissions);
}

$token = $key->identifier . $app->make(Illuminate\Contracts\Encryption\Encrypter::class)->decrypt($key->token);
fwrite(STDOUT, $token);
PHP
}

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
wait_for_service pterodactyl-panel 80

if [ "$seed" = "true" ]; then
  docker compose exec -T backend php artisan migrate:fresh --seed --force --no-interaction
  docker compose exec -T orchestrator php artisan migrate --force --no-interaction
  docker compose exec -T legacy php artisan migrate:fresh --seed --force --no-interaction
  docker compose exec -T pterodactyl-panel php artisan migrate:fresh --seed --force --no-interaction
else
  docker compose exec -T backend php artisan migrate:fresh --force --no-interaction
  docker compose exec -T orchestrator php artisan migrate --force --no-interaction
  docker compose exec -T legacy php artisan migrate:fresh --force --no-interaction
  docker compose exec -T pterodactyl-panel php artisan migrate:fresh --force --no-interaction
fi

seed_pterodactyl_admin_user

pterodactyl_application_api_key="$(generate_pterodactyl_application_api_key)"

docker compose exec -T \
  -e PTERODACTYL_BASE_URL="http://pterodactyl-panel" \
  -e PTERODACTYL_APPLICATION_API_KEY="$pterodactyl_application_api_key" \
  orchestrator php artisan test:provision-local --no-interaction

echo "Platform stack reset complete."
