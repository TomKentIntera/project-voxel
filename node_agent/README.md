# Node Agent

Lightweight Python agent intended to run on each Wings node and report minimal telemetry to the orchestrator.

## What it does

- Discovers running Pterodactyl/Wings servers via local Wings API calls.
- Samples per-server metrics every 5-10 seconds:
  - `cpu_pct` from cgroup CPU usage deltas.
  - `io_write_bytes_per_s` from cgroup I/O write deltas.
- Samples node metrics every 5-10 seconds:
  - `node_cpu_pct` and `node_iowait_pct` from `/proc/stat` deltas.
- Pings Minecraft Java status every 20-30 seconds:
  - `players_online` against `NODE_IP + allocated_port`.
- Posts telemetry to:
  - `POST {ORCHESTRATOR_BASE_URL}/internal/nodes/{NODE_ID}/telemetry`
  - `Authorization: Bearer {NODE_TOKEN}`
- Retries sending with exponential backoff when orchestrator is unreachable (keeps running, no disk persistence).

## Requirements

- Python 3.10+
- Access to:
  - `/proc/stat`
  - `/sys/fs/cgroup`
  - Wings local API endpoint
  - Network path to orchestrator API

## Configuration

Set environment variables:

Required:

- `NODE_ID` - Wings/orchestrator node identifier.
- `NODE_TOKEN` - node telemetry bearer token issued by orchestrator when creating the node record.
- `NODE_IP` - node IP for Minecraft status probes.
- `ORCHESTRATOR_BASE_URL` - include API prefix, e.g. `http://orchestrator.localhost/api`.

Create a node and get its one-time token from orchestrator (admin auth required):

`POST /api/nodes`

Required request payload fields:

```json
{
  "name": "Frankfurt Wings Node",
  "region": "eu.de",
  "ip_address": "203.0.113.10",
  "ptero_location_id": 1,
  "fqdn": "wings-eu-de.example.com",
  "scheme": "https",
  "behind_proxy": true,
  "memory": 32768,
  "memory_overallocate": 0,
  "disk": 204800,
  "disk_overallocate": 0,
  "upload_size": 500,
  "daemon_sftp": 2022,
  "daemon_listen": 8080,
  "allocation_ip": "203.0.113.10",
  "allocation_alias": "wings-eu-de",
  "allocation_ports": ["25565-25580", "30500"]
}
```

Example response excerpt:

```json
{
  "data": {
    "id": "node-1",
    "name": "Frankfurt Wings Node",
    "region": "eu.de",
    "ip_address": "203.0.113.10",
    "sync_status": "pending",
    "node_token": "one-time-raw-token"
  }
}
```

Optional:

- `WINGS_BASE_URL` (default: `http://127.0.0.1:8080`)
- `WINGS_TOKEN` (if Wings local API requires auth)
- `AGENT_HTTP_TIMEOUT_SEC` (default: `5`)
- `AGENT_INSECURE_TLS` (default: `false`)
- `AGENT_SAMPLE_INTERVAL_MIN_SEC` (default: `5`)
- `AGENT_SAMPLE_INTERVAL_MAX_SEC` (default: `10`)
- `AGENT_PLAYERS_INTERVAL_MIN_SEC` (default: `20`)
- `AGENT_PLAYERS_INTERVAL_MAX_SEC` (default: `30`)
- `AGENT_DISCOVERY_INTERVAL_SEC` (default: `15`)
- `AGENT_SEND_BACKOFF_MAX_SEC` (default: `60`)
- `AGENT_MINECRAFT_PING_TIMEOUT_SEC` (default: `3`)

## Run

```bash
cd node_agent
python3 main.py
```
