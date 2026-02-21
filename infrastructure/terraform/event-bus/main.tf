provider "aws" {
  region = var.aws_region

  access_key = var.use_localstack ? var.localstack_access_key : null
  secret_key = var.use_localstack ? var.localstack_secret_key : null

  skip_credentials_validation = var.use_localstack
  skip_metadata_api_check     = var.use_localstack
  skip_requesting_account_id  = var.use_localstack

  dynamic "endpoints" {
    for_each = var.use_localstack ? [1] : []

    content {
      sns = var.localstack_endpoint
      sqs = var.localstack_endpoint
    }
  }
}

resource "aws_sns_topic" "server_orders" {
  name = var.topic_name
}

resource "aws_sqs_queue" "server_orders_dlq" {
  name = var.dead_letter_queue_name
}

resource "aws_sqs_queue" "server_orders_orchestrator" {
  name = var.queue_name

  redrive_policy = jsonencode({
    deadLetterTargetArn = aws_sqs_queue.server_orders_dlq.arn
    maxReceiveCount     = var.max_receive_count
  })
}

resource "aws_sqs_queue_policy" "server_orders_orchestrator" {
  queue_url = aws_sqs_queue.server_orders_orchestrator.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid       = "AllowSnsPublish"
        Effect    = "Allow"
        Principal = { Service = "sns.amazonaws.com" }
        Action    = "sqs:SendMessage"
        Resource  = aws_sqs_queue.server_orders_orchestrator.arn
        Condition = {
          ArnEquals = {
            "aws:SourceArn" = aws_sns_topic.server_orders.arn
          }
        }
      }
    ]
  })
}

resource "aws_sns_topic_subscription" "server_orders_orchestrator" {
  topic_arn = aws_sns_topic.server_orders.arn
  protocol  = "sqs"
  endpoint  = aws_sqs_queue.server_orders_orchestrator.arn

  raw_message_delivery = true

  depends_on = [
    aws_sqs_queue_policy.server_orders_orchestrator,
  ]
}
