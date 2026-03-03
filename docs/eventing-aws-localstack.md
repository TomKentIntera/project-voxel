# Event handling: AWS in production, LocalStack locally

This project uses AWS-style eventing for cross-service communication:

- **Publisher**: store backend
- **Subscriber**: orchestrator
- **Transport**: SNS topic fanout to SQS queue
- **Shared contracts/client**: `packages/php/core-events` (`AbstractEvent`, `ServerOrdered`, `EventBusClient`)

Related orchestration design doc:

- `docs/server-provisioning-orchestration.md` (detailed server provisioning sequence and class responsibilities)

## Resource model

- SNS topic: `server-orders`
- SQS queue: `server-orders-orchestrator`
- Subscription: `server-orders` -> `server-orders-orchestrator`
- SQS DLQ: `server-orders-orchestrator-dlq`
- SQS queue: `server-lifecycle-backend`
- Subscription: `server-orders` -> `server-lifecycle-backend`
- SQS DLQ: `server-lifecycle-backend-dlq`

Each destination app consumes from one queue and then routes by `event_type` to app-local processors.

Resources are provisioned via Terraform from:

- `infrastructure/terraform/event-bus`

## Local setup

1. Start the stack:

```bash
scripts/platform-start.sh
```

On first boot this provisions the LocalStack event bus via Terraform. On subsequent restarts,
`platform-start.sh` detects existing SNS/SQS resources and skips Terraform apply.

Force re-provisioning when needed:

```bash
scripts/platform-start.sh --force-provision
```

2. Or run Terraform manually:

```bash
scripts/event-bus-terraform.sh local apply --auto-approve
```

`orchestrator-event-consumer` runs `php artisan events:consume-server-ordered`.
`orchestrator-worker` runs `php artisan queue:work`.
`backend-event-consumer` runs `php artisan events:consume-server-lifecycle`.
Both consumers dispatch app-local `EventConsumerJob` jobs asynchronously (`dispatch`) and resolve `event_type` to app-local handlers.

## AWS setup

```bash
scripts/event-bus-terraform.sh aws plan
scripts/event-bus-terraform.sh aws apply
```

You can print env exports from Terraform output:

```bash
scripts/event-bus-terraform.sh aws output-env
```

## Environment variables

Both Laravel apps now support these variables:

- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `AWS_SESSION_TOKEN`
- `AWS_DEFAULT_REGION`
- `AWS_ENDPOINT` (set to `http://localstack:4566` in Docker for local)
- `AWS_USE_PATH_STYLE_ENDPOINT`
- `EVENT_BUS_SERVER_ORDERS_TOPIC_ARN`
- `EVENT_BUS_DESTINATION_QUEUE_URL` (preferred per-destination consumer queue URL)
- `EVENT_BUS_SERVER_ORDERS_QUEUE_URL`
- `EVENT_BUS_SERVER_LIFECYCLE_QUEUE_URL`

And `services.event_bus.topics` maps event type -> topic ARN (for example `server.ordered.v1`).

Queue config also supports the standard Laravel SQS variables:

- `SQS_PREFIX`
- `SQS_QUEUE`
- `SQS_SUFFIX`

## Local smoke test

Publish an event through backend:

```bash
docker compose exec backend php artisan events:publish-server-ordered <server-uuid>
```

Publish directly to SNS:

```bash
docker compose exec localstack awslocal sns publish \
  --topic-arn arn:aws:sns:us-east-1:000000000000:server-orders \
  --message '{"event_id":"manual-demo","event_type":"server.ordered.v1","occurred_at":"2026-02-20T00:00:00Z","server_id":1,"server_uuid":"demo-uuid","user_id":1,"plan":"starter","config":{}}'
```

Receive from queue:

```bash
docker compose exec localstack awslocal sqs receive-message \
  --queue-url http://localhost:4566/000000000000/server-orders-orchestrator
```

Example lifecycle event payload (consumed by backend lifecycle invalidator):

```bash
docker compose exec localstack awslocal sns publish \
  --topic-arn arn:aws:sns:us-east-1:000000000000:server-orders \
  --message '{"event_id":"manual-provisioned","event_type":"server.provisioned","occurred_at":"2026-02-20T00:00:00Z","server_id":1}'
```
