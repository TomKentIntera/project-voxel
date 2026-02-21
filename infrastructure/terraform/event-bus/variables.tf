variable "aws_region" {
  description = "AWS region used for SNS/SQS resources."
  type        = string
  default     = "us-east-1"
}

variable "use_localstack" {
  description = "When true, provisions resources against LocalStack using static test credentials."
  type        = bool
  default     = false
}

variable "localstack_endpoint" {
  description = "LocalStack endpoint used by the AWS provider when use_localstack=true."
  type        = string
  default     = "http://localhost:4566"
}

variable "localstack_access_key" {
  description = "Access key used for LocalStack."
  type        = string
  default     = "test"
}

variable "localstack_secret_key" {
  description = "Secret key used for LocalStack."
  type        = string
  default     = "test"
}

variable "topic_name" {
  description = "SNS topic name for server ordered events."
  type        = string
  default     = "server-orders"
}

variable "queue_name" {
  description = "SQS queue name consumed by orchestrator workers."
  type        = string
  default     = "server-orders-orchestrator"
}

variable "dead_letter_queue_name" {
  description = "SQS dead-letter queue name for failed deliveries."
  type        = string
  default     = "server-orders-orchestrator-dlq"
}

variable "max_receive_count" {
  description = "How many receive attempts before moving a message to the DLQ."
  type        = number
  default     = 5
}
