#!/usr/bin/env bash
set -euo pipefail

AWS_REGION="${AWS_DEFAULT_REGION:-us-east-1}"
ACCOUNT_ID="000000000000"
TOPIC_NAME="${EVENT_BUS_SERVER_ORDERS_TOPIC_NAME:-server-orders}"
QUEUE_NAME="${EVENT_BUS_SERVER_ORDERS_QUEUE_NAME:-server-orders-orchestrator}"
TOPIC_ARN="arn:aws:sns:${AWS_REGION}:${ACCOUNT_ID}:${TOPIC_NAME}"
QUEUE_URL="http://localhost:4566/${ACCOUNT_ID}/${QUEUE_NAME}"

echo "Bootstrapping LocalStack event bus resources..."
echo "Topic: ${TOPIC_NAME}"
echo "Queue: ${QUEUE_NAME}"

awslocal sns create-topic --name "${TOPIC_NAME}" >/dev/null
awslocal sqs create-queue --queue-name "${QUEUE_NAME}" >/dev/null

QUEUE_ARN="$(awslocal sqs get-queue-attributes \
    --queue-url "${QUEUE_URL}" \
    --attribute-names QueueArn \
    --query 'Attributes.QueueArn' \
    --output text)"

POLICY=$(cat <<JSON
{"Version":"2012-10-17","Statement":[{"Sid":"AllowSnsPublish","Effect":"Allow","Principal":{"Service":"sns.amazonaws.com"},"Action":"sqs:SendMessage","Resource":"${QUEUE_ARN}","Condition":{"ArnEquals":{"aws:SourceArn":"${TOPIC_ARN}"}}}]}
JSON
)

awslocal sqs set-queue-attributes \
    --queue-url "${QUEUE_URL}" \
    --attributes Policy="${POLICY}" >/dev/null

awslocal sns subscribe \
    --topic-arn "${TOPIC_ARN}" \
    --protocol sqs \
    --notification-endpoint "${QUEUE_ARN}" >/dev/null

echo "LocalStack event bus ready."
