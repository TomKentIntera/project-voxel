# Server provisioning orchestration

This document defines the server provisioning flow and the step-by-step sequence
we want inside the orchestrator provisioning class.

It covers:

- what is already implemented,
- what is still missing,
- the exact sequence the new orchestrator provisioning class should run.

---

## Current state (implemented today)

### 1) Backend publishes `server.ordered.v1` after successful payment

- Trigger point: `platform/backend/app/Services/Stripe/Services/StripeWebhookService.php`
- Method: `handleInvoicePaymentSucceeded()`
- Behavior:
  - sets server status to `provisioning` for first-time paid servers,
  - publishes `ServerOrdered` event (`server.ordered.v1`) via `EventBusClient`,
  - writes a `server_events` row with type `server.ordered.v1`.

### 2) Orchestrator consumes lifecycle events from SQS

- Entrypoint command: `php artisan events:consume-server-ordered`
- Command file: `platform/orchestrator/app/Console/Commands/ConsumeServerOrderedEventsCommand.php`
- Consumer file: `platform/orchestrator/app/Services/EventBus/ServerOrderedConsumer.php`
- Processor map: `platform/orchestrator/app/Services/EventBus/ServerLifecycleEventProcessorMap.php`

### 3) Orchestrator currently handles ordered/provisioned lifecycle bookkeeping

- `ServerOrderedLifecycleEventConsumer`:
  - parses the payload as `ServerOrdered`,
  - sets server status to `provisioning`,
  - writes `server.provisioning.started`,
  - deduplicates by `event_id`.

- `ServerProvisionedLifecycleEventConsumer`:
  - sets server status to `provisioned`,
  - sets `initialised=true`,
  - writes `server.provisioned`,
  - deduplicates by `event_id`,
  - dispatches email + Slack notifications.

### Gap

The orchestrator does **not** currently create the Pterodactyl server instance.
That orchestration path is what this document defines.

---

## Target: provisioning class in orchestrator

Proposed class:

- `App\Services\Provisioning\ServerProvisioningOrchestrator`

Proposed entrypoint method:

- `provisionFromOrderedEvent(ServerOrdered $event): void`

The class should orchestrate destination selection, Pterodactyl resource
creation, DB mapping persistence, and publish the completion lifecycle event.

---

## Step-by-step sequence for `ServerProvisioningOrchestrator`

1. **Load + validate the target server**
   - Lookup by both `server_id` and `server_uuid`.
   - Fail fast if not found (non-retryable).
   - Ensure status is compatible (`new` or `provisioning`).

2. **Idempotency guard**
   - If the same `event_id` already produced provisioning work, return.
   - If `servers.ptero_id` is already set, treat as already provisioned and move
     to publish/confirm step.
   - Use an application lock keyed by server ID (and/or event ID) to prevent
     concurrent duplicate provisioning.

3. **Resolve plan + runtime config**
   - Read plan from `servers.plan`.
   - Read server-specific config JSON from `servers.config` (name, requested
     region, optional runtime overrides).
   - Resolve required RAM from plan metadata.

4. **Select destination node**
   - Call `ServerDestinationOrchestratorService::resolve(planName, region, serverId)`.
   - Require a non-null destination.
   - Keep top-ranked node ID and region for audit metadata.

5. **Ensure node is synced to Pterodactyl**
   - Require a valid `nodes.ptero_node_id`.
   - If missing, run `SyncNodeToPterodactylJob::dispatchSync($nodeId)` and reload.
   - Fail as retryable if panel node ID still missing.

6. **Resolve or create panel user**
   - Resolve local owner (`servers.user_id` -> `users`).
   - Lookup panel user by `external_id` using `PterodactylApiClient::findUserByExternalId()`.
   - Create panel user if missing via `PterodactylApiClient::createUser()`.

7. **Reserve/select allocation**
   - Fetch node allocations via `PterodactylApiClient::listNodeAllocations($pteroNodeId)`.
   - Pick the first unassigned allocation (deterministic order: port asc).
   - Fail retryable if no free allocation exists.

8. **Build panel server create payload**
   - `external_id`: stable server identifier (recommend `servers.uuid`).
   - `name`: from server config fallback.
   - `user`: panel user ID.
   - `node_id`: selected node panel ID.
   - `allocation.default`: selected allocation ID.
   - `limits`: plan-derived memory (+ cpu/disk defaults).
   - `feature_limits`: safe defaults.
   - `egg/startup/environment/image`: from plan-to-panel mapping config.

9. **Create the panel server**
   - Call `PterodactylApiClient::createServer($payload)`.
   - On timeout/5xx, re-check by `external_id` before retrying to avoid duplicates.

10. **Persist orchestration result**
    - Save `servers.ptero_id`.
    - Merge provisioning metadata into `servers.config` (selected node, region,
      allocation ID/port, panel server UUID if available).
    - Write/update an audit-style server event metadata entry as needed.

11. **Publish provisioning completion event**
    - Publish `server.provisioned` (or versioned `server.provisioned.v1`) with:
      - `event_id`,
      - `correlation_id` (carry from ordered event),
      - `occurred_at`,
      - `server_id`,
      - optional `server_uuid`, `ptero_id`, `node_id`, `allocation_id`.
    - This keeps backend and orchestrator lifecycle consumers in sync.

12. **Let existing consumers finalize status + notifications**
    - `ServerProvisionedLifecycleEventConsumer` sets `status=provisioned`,
      `initialised=true`, writes `server.provisioned`, sends notifications.

---

## Error handling policy

- **Retryable errors** (queue retry):
  - temporary Pterodactyl/API failures,
  - no free allocation currently available,
  - transient event bus publish failures.

- **Non-retryable errors** (mark failed, alert):
  - malformed event payload,
  - unknown server ID/UUID combination,
  - invalid/nonexistent plan configuration.

- **Duplicate safety**:
  - dedupe by `event_id`,
  - lock per server,
  - always check existing `ptero_id` and panel `external_id` before creating.

---

## Suggested implementation wiring

1. Keep `ServerOrderedConsumer` and fan-out pattern as-is.
2. In `ServerOrderedLifecycleEventConsumer`, dispatch a dedicated provisioning job
   after writing `server.provisioning.started`.
3. Job invokes `ServerProvisioningOrchestrator`.
4. Add a shared event class for provisioned events under
   `packages/php/core-events/src/Events` (recommended: `ServerProvisioned`).
5. Add topic mapping for provisioned event type(s) in service config.

---

## Minimal checklist

- [ ] Add `ServerProvisioningOrchestrator` service class.
- [ ] Add provisioning job and queue retry policy.
- [ ] Add/confirm plan -> panel payload mapping config.
- [ ] Add `ServerProvisioned` core event contract.
- [ ] Publish provisioned event from orchestrator after successful create.
- [ ] Add unit + feature tests for idempotency and duplicate protection.
