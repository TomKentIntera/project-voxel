# tcp-proxy (local dev)

Single-container TCP proxy for local development that runs:

1. **HAProxy** in TCP mode
2. **Python worker** that polls the orchestrator bindings API and reloads HAProxy when bindings change

This proxy supports both:

- Minecraft Java traffic (`kind=game`)
- SFTP traffic (`kind=sftp`)

Each public port maps to exactly one backend `target_host:target_port`.

## Files

- `Dockerfile`
- `entrypoint.sh`
- `worker.py`
- `docker-compose.yml` (example)
- `.env` (committed local defaults for compose + worker)

## Environment variables

### Required

- `ORCH_BASE_URL` (example: `http://orchestrator:8080`)
- `ORCH_TOKEN`
- `PROXY_ID`
- `PROXY_REGION`

### Optional

- `POLL_INTERVAL_SECONDS` (default: `15`)
- `GAME_LISTEN_RANGE` (default: `25565-25665`)
- `SFTP_LISTEN_RANGE` (default: `30500-31000`)
- `HAPROXY_CFG` (default: `/etc/haproxy/haproxy.cfg`)
- `HAPROXY_PIDFILE` (default: `/run/haproxy.pid`)
- `STATE_DIR` (default: `/var/lib/proxy-sync`)

For local compose runs, these are prefilled in `.env`.

## Orchestrator API contract

Worker polling request:

- `GET {ORCH_BASE_URL}/api/internal/proxy-bindings`
- Headers:
  - `Authorization: Bearer {ORCH_TOKEN}`
  - `X-Proxy-Id: {PROXY_ID}`
  - `X-Proxy-Region: {PROXY_REGION}`

For backward compatibility, the worker also retries `.../internal/proxy-bindings`
and `.../api/regional-proxies/mappings` when the preferred endpoint returns `404`.

Response body: JSON list of bindings:

```json
[
  {
    "kind": "game",
    "listen_port": 25565,
    "target_host": "mc-1",
    "target_port": 25565,
    "enabled": true,
    "updated_at": "2026-01-01T00:00:00Z"
  }
]
```

## Runtime behavior

On startup, the worker:

1. Validates required env vars.
2. Fetches bindings from orchestrator.
3. Filters `enabled=true`, splits by `kind`, sorts by `listen_port`.
4. Skips duplicate `(kind, listen_port)` entries (logs and keeps first).
5. Renders:
   - `/etc/haproxy/haproxy.cfg`
   - `/etc/haproxy/game_port_to_backend.map`
   - `/etc/haproxy/sftp_port_to_backend.map`
6. Writes files atomically.
7. Validates config with `haproxy -c`.
8. Starts HAProxy in master-worker mode:
   - `haproxy -W -db -f /etc/haproxy/haproxy.cfg -p /run/haproxy.pid`

On each poll:

- Fetch and render again (deterministic order).
- Compute SHA256 of rendered outputs.
- If unchanged: do nothing.
- If changed:
  - write atomically
  - validate with `haproxy -c`
  - if valid, graceful reload with `-sf <old-pids>`
  - if invalid, restore previous files and skip reload

If API call fails, it logs the error and keeps running with current config.

## Run locally

From this directory:

```bash
docker compose up --build
```

Edit `.env` first if you need to change orchestrator URL/token, proxy metadata, or published port ranges.

Key compose variables in `.env`:

- `GAME_PORT_PUBLISH` (default `25565-25665:25565-25665/tcp`)
- `SFTP_PORT_PUBLISH` (default `30500-31000:30500-31000/tcp`)
