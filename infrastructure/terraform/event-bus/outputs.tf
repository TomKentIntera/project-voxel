output "topic_arn" {
  description = "SNS topic ARN for server ordered events."
  value       = aws_sns_topic.server_orders.arn
}

output "queue_arn" {
  description = "SQS queue ARN consumed by orchestrator."
  value       = aws_sqs_queue.server_orders_orchestrator.arn
}

output "queue_url" {
  description = "SQS queue URL consumed by orchestrator."
  value       = aws_sqs_queue.server_orders_orchestrator.id
}

output "dead_letter_queue_arn" {
  description = "SQS dead-letter queue ARN."
  value       = aws_sqs_queue.server_orders_dlq.arn
}

output "dead_letter_queue_url" {
  description = "SQS dead-letter queue URL."
  value       = aws_sqs_queue.server_orders_dlq.id
}

output "subscription_arn" {
  description = "SNS subscription ARN for the orchestrator queue."
  value       = aws_sns_topic_subscription.server_orders_orchestrator.arn
}

output "event_bus_env" {
  description = "Convenience map for app environment variables."
  value = {
    EVENT_BUS_SERVER_ORDERS_TOPIC_ARN = aws_sns_topic.server_orders.arn
    EVENT_BUS_SERVER_ORDERS_QUEUE_URL = aws_sqs_queue.server_orders_orchestrator.id
    EVENT_BUS_SERVER_LIFECYCLE_QUEUE_URL = aws_sqs_queue.server_lifecycle_backend.id
  }
}

output "lifecycle_queue_arn" {
  description = "SQS queue ARN consumed by backend lifecycle invalidator."
  value       = aws_sqs_queue.server_lifecycle_backend.arn
}

output "lifecycle_queue_url" {
  description = "SQS queue URL consumed by backend lifecycle invalidator."
  value       = aws_sqs_queue.server_lifecycle_backend.id
}

output "lifecycle_dead_letter_queue_arn" {
  description = "SQS dead-letter queue ARN for lifecycle queue."
  value       = aws_sqs_queue.server_lifecycle_backend_dlq.arn
}

output "lifecycle_dead_letter_queue_url" {
  description = "SQS dead-letter queue URL for lifecycle queue."
  value       = aws_sqs_queue.server_lifecycle_backend_dlq.id
}

output "lifecycle_subscription_arn" {
  description = "SNS subscription ARN for the backend lifecycle queue."
  value       = aws_sns_topic_subscription.server_lifecycle_backend.arn
}
