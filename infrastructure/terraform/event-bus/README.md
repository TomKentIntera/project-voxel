# Event bus Terraform stack

This stack provisions the event transport resources used by the platform:

- SNS topic for `server.ordered.v1`
- SQS consumer queue for orchestrator
- SQS dead-letter queue
- SNS -> SQS subscription with `raw_message_delivery = true`
- Queue policy allowing only the SNS topic to publish

## Files

- `main.tf` - AWS provider + SNS/SQS resources
- `variables.tf` - configurable names and runtime options
- `outputs.tf` - ARNs/URLs and env map outputs
- `versions.tf` - Terraform/provider constraints

## Quick start

From repository root:

```bash
# LocalStack
scripts/event-bus-terraform.sh local apply --auto-approve

# AWS
scripts/event-bus-terraform.sh aws plan
scripts/event-bus-terraform.sh aws apply
```

## Useful outputs

```bash
scripts/event-bus-terraform.sh local output
scripts/event-bus-terraform.sh local output-env
```

`output-env` prints shell `export` lines for:

- `EVENT_BUS_SERVER_ORDERS_TOPIC_ARN`
- `EVENT_BUS_SERVER_ORDERS_QUEUE_URL`
