# Event handling: AWS in production, LocalStack locally

This project uses AWS-style eventing for cross-service communication:

- **Publisher**: store backend
- **Subscriber**: orchestrator
- **Transport**: SNS topic fanout to SQS queue
- **Shared contracts/client**: `packages/php/core-events` (`AbstractEvent`, `ServerOrdered`, `EventBusClient`)

## Resource model

- SNS topic: `server-orders`
- SQS queue: `server-orders-orchestrator`
- Subscription: `server-orders` -> `server-orders-orchestrator`

For local development, Docker Compose starts LocalStack and auto-creates these resources.

## Local setup

LocalStack is defined in `docker-compose.yml` and configured to load init scripts from:

- `docker/localstack/init`
- `orchestrator-worker` runs `php artisan events:consume-server-ordered`

The bootstrap script:

- creates the SNS topic
- creates the SQS queue
- grants SNS permission to publish to that queue
- subscribes the queue to the topic (raw message delivery)

## Environment variables

Both Laravel apps now support these variables:

- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `AWS_SESSION_TOKEN`
- `AWS_DEFAULT_REGION`
- `AWS_ENDPOINT` (set to `http://localstack:4566` in Docker for local)
- `AWS_USE_PATH_STYLE_ENDPOINT`
- `EVENT_BUS_SERVER_ORDERS_TOPIC_ARN`
- `EVENT_BUS_SERVER_ORDERS_QUEUE_URL`

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
