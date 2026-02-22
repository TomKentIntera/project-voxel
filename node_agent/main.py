#!/usr/bin/env python3
"""Lightweight Wings node telemetry agent.

Collects:
- Node CPU and iowait from /proc/stat
- Per-container CPU and write I/O from cgroup deltas
- Minecraft Java players_online via status ping

And posts compact telemetry to:
POST /internal/nodes/{node_id}/telemetry
"""

from __future__ import annotations

import json
import os
import random
import socket
import ssl
import struct
import time
import urllib.error
import urllib.parse
import urllib.request
from dataclasses import dataclass
from datetime import datetime, timezone
from typing import Any, Optional


def log(message: str) -> None:
    timestamp = datetime.now(timezone.utc).isoformat()
    print(f"{timestamp} {message}", flush=True)


def read_file(path: str) -> Optional[str]:
    try:
        with open(path, "r", encoding="utf-8") as handle:
            return handle.read()
    except OSError:
        return None


def env_bool(name: str, default: bool) -> bool:
    raw = os.getenv(name)
    if raw is None:
        return default

    normalized = raw.strip().lower()
    if normalized in {"1", "true", "yes", "on"}:
        return True
    if normalized in {"0", "false", "no", "off"}:
        return False
    return default


def env_float(name: str, default: float) -> float:
    raw = os.getenv(name)
    if raw is None:
        return default

    try:
        return float(raw)
    except ValueError:
        return default


@dataclass(frozen=True)
class AgentConfig:
    node_id: str
    node_token: str
    node_ip: str
    orchestrator_base_url: str
    wings_base_url: str
    wings_token: Optional[str]
    http_timeout_sec: float
    insecure_tls: bool
    sample_interval_min_sec: float
    sample_interval_max_sec: float
    players_interval_min_sec: float
    players_interval_max_sec: float
    discovery_interval_sec: float
    send_backoff_max_sec: float
    minecraft_ping_timeout_sec: float

    @staticmethod
    def from_env() -> "AgentConfig":
        node_id = os.getenv("NODE_ID", "").strip()
        node_token = os.getenv("NODE_TOKEN", "").strip()
        node_ip = os.getenv("NODE_IP", "").strip()
        orchestrator_base_url = os.getenv("ORCHESTRATOR_BASE_URL", "").strip().rstrip("/")
        wings_base_url = os.getenv("WINGS_BASE_URL", "http://127.0.0.1:8080").strip().rstrip("/")
        wings_token = os.getenv("WINGS_TOKEN")
        wings_token = wings_token.strip() if isinstance(wings_token, str) and wings_token.strip() else None

        if not node_id:
            raise ValueError("NODE_ID is required")
        if not node_token:
            raise ValueError("NODE_TOKEN is required")
        if not node_ip:
            raise ValueError("NODE_IP is required")
        if not orchestrator_base_url:
            raise ValueError("ORCHESTRATOR_BASE_URL is required")

        sample_min = env_float("AGENT_SAMPLE_INTERVAL_MIN_SEC", 5.0)
        sample_max = env_float("AGENT_SAMPLE_INTERVAL_MAX_SEC", 10.0)
        players_min = env_float("AGENT_PLAYERS_INTERVAL_MIN_SEC", 20.0)
        players_max = env_float("AGENT_PLAYERS_INTERVAL_MAX_SEC", 30.0)

        if sample_min <= 0 or sample_max < sample_min:
            raise ValueError("AGENT_SAMPLE_INTERVAL_* values are invalid")
        if players_min <= 0 or players_max < players_min:
            raise ValueError("AGENT_PLAYERS_INTERVAL_* values are invalid")

        return AgentConfig(
            node_id=node_id,
            node_token=node_token,
            node_ip=node_ip,
            orchestrator_base_url=orchestrator_base_url,
            wings_base_url=wings_base_url,
            wings_token=wings_token,
            http_timeout_sec=env_float("AGENT_HTTP_TIMEOUT_SEC", 5.0),
            insecure_tls=env_bool("AGENT_INSECURE_TLS", False),
            sample_interval_min_sec=sample_min,
            sample_interval_max_sec=sample_max,
            players_interval_min_sec=players_min,
            players_interval_max_sec=players_max,
            discovery_interval_sec=env_float("AGENT_DISCOVERY_INTERVAL_SEC", 15.0),
            send_backoff_max_sec=env_float("AGENT_SEND_BACKOFF_MAX_SEC", 60.0),
            minecraft_ping_timeout_sec=env_float("AGENT_MINECRAFT_PING_TIMEOUT_SEC", 3.0),
        )


def http_json_request(
    method: str,
    url: str,
    timeout_sec: float,
    insecure_tls: bool,
    bearer_token: Optional[str] = None,
    payload: Optional[dict[str, Any]] = None,
) -> Any:
    headers = {
        "Accept": "application/json",
    }

    body: Optional[bytes] = None
    if payload is not None:
        body = json.dumps(payload).encode("utf-8")
        headers["Content-Type"] = "application/json"

    if bearer_token:
        headers["Authorization"] = f"Bearer {bearer_token}"

    request = urllib.request.Request(url=url, method=method, headers=headers, data=body)

    ssl_context = ssl._create_unverified_context() if insecure_tls else None
    with urllib.request.urlopen(request, timeout=timeout_sec, context=ssl_context) as response:
        content = response.read()
        if not content:
            return {}
        return json.loads(content.decode("utf-8"))


@dataclass(frozen=True)
class DiscoveredServer:
    server_id: str
    container_id: str
    allocated_port: int


class WingsDiscoverer:
    # Wings and panel deployments vary; try a few common local endpoints.
    CANDIDATE_ENDPOINTS = (
        "/api/servers",
        "/api/system/servers",
        "/api/application/servers",
        "/api/servers/list",
    )

    def __init__(self, config: AgentConfig) -> None:
        self.config = config

    def discover_servers(self) -> list[DiscoveredServer]:
        last_error: Optional[Exception] = None

        for endpoint in self.CANDIDATE_ENDPOINTS:
            url = f"{self.config.wings_base_url}{endpoint}"
            try:
                payload = http_json_request(
                    method="GET",
                    url=url,
                    timeout_sec=self.config.http_timeout_sec,
                    insecure_tls=self.config.insecure_tls,
                    bearer_token=self.config.wings_token,
                )
            except Exception as exc:  # noqa: BLE001
                last_error = exc
                continue

            return self._extract_servers(payload)

        if last_error is not None:
            log(f"wings discovery failed: {last_error}")
        return []

    def _extract_servers(self, payload: Any) -> list[DiscoveredServer]:
        discovered: dict[str, DiscoveredServer] = {}

        for candidate in self._iter_dicts(payload):
            server_id = self._extract_server_id(candidate)
            container_id = self._extract_container_id(candidate)
            port = self._extract_allocated_port(candidate)

            if server_id is None or container_id is None or port is None:
                continue

            if not self._is_running(candidate):
                continue

            discovered[server_id] = DiscoveredServer(
                server_id=server_id,
                container_id=container_id,
                allocated_port=port,
            )

        return sorted(discovered.values(), key=lambda server: server.server_id)

    def _iter_dicts(self, value: Any) -> list[dict[str, Any]]:
        stack = [value]
        results: list[dict[str, Any]] = []

        while stack:
            current = stack.pop()
            if isinstance(current, dict):
                results.append(current)
                for child in current.values():
                    stack.append(child)
            elif isinstance(current, list):
                for child in current:
                    stack.append(child)

        return results

    def _extract_server_id(self, data: dict[str, Any]) -> Optional[str]:
        for key in ("server_id", "uuid", "identifier", "id"):
            value = data.get(key)
            if isinstance(value, str) and value.strip():
                return value.strip()
        return None

    def _extract_container_id(self, data: dict[str, Any]) -> Optional[str]:
        candidates: list[Any] = [
            data.get("container_id"),
            data.get("container"),
            data.get("docker_container"),
            data.get("docker_id"),
        ]

        container_block = data.get("container")
        if isinstance(container_block, dict):
            candidates.extend(
                [
                    container_block.get("id"),
                    container_block.get("container_id"),
                    container_block.get("docker_id"),
                ]
            )

        for value in candidates:
            if isinstance(value, str):
                normalized = value.strip().removeprefix("docker://")
                if normalized:
                    return normalized
        return None

    def _extract_allocated_port(self, data: dict[str, Any]) -> Optional[int]:
        direct_candidates = [
            data.get("allocated_port"),
            data.get("allocation_port"),
            data.get("game_port"),
            data.get("port"),
        ]
        for candidate in direct_candidates:
            port = self._parse_port(candidate)
            if port is not None:
                return port

        allocation = data.get("allocation")
        if isinstance(allocation, dict):
            port = self._parse_port(allocation.get("port"))
            if port is not None:
                return port

        allocations = data.get("allocations")
        if isinstance(allocations, list):
            for entry in allocations:
                if isinstance(entry, dict):
                    port = self._parse_port(entry.get("port"))
                    if port is not None:
                        return port
        elif isinstance(allocations, dict):
            port = self._parse_port(allocations.get("port"))
            if port is not None:
                return port

        return None

    def _parse_port(self, value: Any) -> Optional[int]:
        if isinstance(value, int):
            port = value
        elif isinstance(value, str) and value.strip().isdigit():
            port = int(value.strip())
        else:
            return None

        if 1 <= port <= 65535:
            return port
        return None

    def _is_running(self, data: dict[str, Any]) -> bool:
        running_flag = data.get("running")
        if isinstance(running_flag, bool):
            return running_flag

        status = str(data.get("status", data.get("state", data.get("current_state", "")))).strip().lower()
        if status in {"running", "online", "started", "on"}:
            return True
        if status in {"stopped", "offline", "suspended", "installing", "stopping"}:
            return False

        # If status is unknown, keep the server as long as it has
        # the fields needed for telemetry sampling.
        return True


class CgroupResolver:
    def __init__(self, cgroup_root: str = "/sys/fs/cgroup") -> None:
        self.cgroup_root = cgroup_root
        self._cache: dict[str, Optional[str]] = {}

    def resolve(self, container_id: str) -> Optional[str]:
        if container_id in self._cache:
            cached = self._cache[container_id]
            if cached and os.path.isdir(cached):
                return cached

        identifiers = [container_id]
        if len(container_id) >= 12:
            identifiers.append(container_id[:12])

        for identifier in identifiers:
            for candidate in self._candidate_paths(identifier):
                if self._looks_like_cgroup_path(candidate):
                    self._cache[container_id] = candidate
                    return candidate

        discovered = self._search_cgroup_tree(identifiers)
        self._cache[container_id] = discovered
        return discovered

    def _candidate_paths(self, identifier: str) -> list[str]:
        root = self.cgroup_root
        return [
            os.path.join(root, "system.slice", f"docker-{identifier}.scope"),
            os.path.join(root, "system.slice", f"containerd-{identifier}.scope"),
            os.path.join(root, "docker", identifier),
            os.path.join(root, identifier),
        ]

    def _looks_like_cgroup_path(self, path: str) -> bool:
        if not os.path.isdir(path):
            return False

        return any(
            os.path.exists(os.path.join(path, filename))
            for filename in ("cpu.stat", "cpuacct.usage")
        )

    def _search_cgroup_tree(self, identifiers: list[str]) -> Optional[str]:
        if not os.path.isdir(self.cgroup_root):
            return None

        for current_root, dir_names, _ in os.walk(self.cgroup_root):
            for dir_name in dir_names:
                if not any(identifier in dir_name for identifier in identifiers):
                    continue

                candidate = os.path.join(current_root, dir_name)
                if self._looks_like_cgroup_path(candidate):
                    return candidate

        return None


def read_cgroup_cpu_usage_usec(cgroup_path: str) -> Optional[int]:
    cpu_stat = read_file(os.path.join(cgroup_path, "cpu.stat"))
    if cpu_stat:
        for line in cpu_stat.splitlines():
            parts = line.strip().split()
            if len(parts) != 2:
                continue
            key, raw_value = parts
            if not raw_value.isdigit():
                continue
            if key == "usage_usec":
                return int(raw_value)
            if key == "usage_nsec":
                return int(raw_value) // 1000

    cpuacct_usage = read_file(os.path.join(cgroup_path, "cpuacct.usage"))
    if cpuacct_usage and cpuacct_usage.strip().isdigit():
        return int(cpuacct_usage.strip()) // 1000

    return None


def read_cgroup_write_bytes(cgroup_path: str) -> Optional[int]:
    io_stat = read_file(os.path.join(cgroup_path, "io.stat"))
    if io_stat:
        total_write = 0
        found = False
        for line in io_stat.splitlines():
            for field in line.strip().split():
                if not field.startswith("wbytes="):
                    continue
                raw_value = field.split("=", 1)[1]
                if raw_value.isdigit():
                    total_write += int(raw_value)
                    found = True
        if found:
            return total_write

    blkio_stat = read_file(os.path.join(cgroup_path, "blkio.throttle.io_service_bytes"))
    if blkio_stat:
        total_write = 0
        found = False
        for line in blkio_stat.splitlines():
            parts = line.strip().split()
            if len(parts) != 3:
                continue
            _, op, raw_value = parts
            if op.lower() == "write" and raw_value.isdigit():
                total_write += int(raw_value)
                found = True
        if found:
            return total_write

    return None


@dataclass
class ServerRuntimeState:
    last_cpu_usage_usec: Optional[int] = None
    last_write_bytes: Optional[int] = None
    last_sample_monotonic: Optional[float] = None
    players_online: Optional[int] = None
    next_players_probe_epoch: float = 0.0


@dataclass(frozen=True)
class NodeMetrics:
    cpu_pct: float
    iowait_pct: float


@dataclass(frozen=True)
class ProcStatSnapshot:
    total: int
    idle: int
    iowait: int


def read_proc_stat_snapshot() -> Optional[ProcStatSnapshot]:
    proc_stat = read_file("/proc/stat")
    if proc_stat is None:
        return None

    for line in proc_stat.splitlines():
        if not line.startswith("cpu "):
            continue

        parts = line.split()
        # cpu user nice system idle iowait irq softirq steal guest guest_nice
        if len(parts) < 6:
            return None

        values: list[int] = []
        for token in parts[1:]:
            if token.isdigit():
                values.append(int(token))
            else:
                return None

        if len(values) < 5:
            return None

        total = sum(values)
        idle = values[3]
        iowait = values[4]
        return ProcStatSnapshot(total=total, idle=idle, iowait=iowait)

    return None


class NodeMetricTracker:
    def __init__(self) -> None:
        self._previous: Optional[ProcStatSnapshot] = None

    def sample(self) -> NodeMetrics:
        current = read_proc_stat_snapshot()
        if current is None:
            return NodeMetrics(cpu_pct=0.0, iowait_pct=0.0)

        if self._previous is None:
            self._previous = current
            return NodeMetrics(cpu_pct=0.0, iowait_pct=0.0)

        total_delta = current.total - self._previous.total
        idle_delta = current.idle - self._previous.idle
        iowait_delta = current.iowait - self._previous.iowait
        self._previous = current

        if total_delta <= 0:
            return NodeMetrics(cpu_pct=0.0, iowait_pct=0.0)

        idle_delta = max(idle_delta, 0)
        iowait_delta = max(iowait_delta, 0)
        busy_delta = max(total_delta - idle_delta - iowait_delta, 0)

        return NodeMetrics(
            cpu_pct=(busy_delta / total_delta) * 100.0,
            iowait_pct=(iowait_delta / total_delta) * 100.0,
        )


def encode_varint(value: int) -> bytes:
    output = bytearray()
    # Keep in unsigned 32-bit representation for protocol compatibility.
    value &= 0xFFFFFFFF
    while True:
        byte = value & 0x7F
        value >>= 7
        if value:
            byte |= 0x80
        output.append(byte)
        if not value:
            break
    return bytes(output)


def decode_varint(sock: socket.socket) -> int:
    num_read = 0
    result = 0
    while True:
        raw = sock.recv(1)
        if not raw:
            raise ConnectionError("unexpected EOF while reading varint")

        value = raw[0]
        result |= (value & 0x7F) << (7 * num_read)
        num_read += 1

        if num_read > 5:
            raise ValueError("varint too long")

        if (value & 0x80) == 0:
            break

    return result


def recv_exact(sock: socket.socket, size: int) -> bytes:
    data = bytearray()
    while len(data) < size:
        chunk = sock.recv(size - len(data))
        if not chunk:
            raise ConnectionError("unexpected EOF while reading packet body")
        data.extend(chunk)
    return bytes(data)


def encode_mc_string(value: str) -> bytes:
    encoded = value.encode("utf-8")
    return encode_varint(len(encoded)) + encoded


def minecraft_players_online(host: str, port: int, timeout_sec: float) -> Optional[int]:
    try:
        with socket.create_connection((host, port), timeout=timeout_sec) as sock:
            sock.settimeout(timeout_sec)

            # Handshake packet (state: status)
            handshake_payload = b"".join(
                [
                    encode_varint(0x00),        # packet id
                    encode_varint(754),         # protocol version (modern MC)
                    encode_mc_string(host),     # server address
                    struct.pack(">H", port),    # server port
                    encode_varint(0x01),        # next state: status
                ]
            )
            sock.sendall(encode_varint(len(handshake_payload)) + handshake_payload)

            # Status request packet
            request_payload = encode_varint(0x00)
            sock.sendall(encode_varint(len(request_payload)) + request_payload)

            # Read response packet
            _packet_length = decode_varint(sock)
            packet_id = decode_varint(sock)
            if packet_id != 0x00:
                return None

            json_length = decode_varint(sock)
            raw_json = recv_exact(sock, json_length)
            payload = json.loads(raw_json.decode("utf-8"))
            players = payload.get("players", {}).get("online")
            if isinstance(players, int) and players >= 0:
                return players
            return None
    except Exception:  # noqa: BLE001
        return None


def sample_server_metrics(
    server: DiscoveredServer,
    state: ServerRuntimeState,
    resolver: CgroupResolver,
    now_monotonic: float,
) -> tuple[float, float]:
    cgroup_path = resolver.resolve(server.container_id)
    if cgroup_path is None:
        return 0.0, 0.0

    cpu_usage_usec = read_cgroup_cpu_usage_usec(cgroup_path)
    write_bytes = read_cgroup_write_bytes(cgroup_path)
    if cpu_usage_usec is None or write_bytes is None:
        return 0.0, 0.0

    cpu_pct = 0.0
    io_write_bytes_per_s = 0.0

    if (
        state.last_sample_monotonic is not None
        and state.last_cpu_usage_usec is not None
        and state.last_write_bytes is not None
    ):
        elapsed = now_monotonic - state.last_sample_monotonic
        if elapsed > 0:
            cpu_delta = max(cpu_usage_usec - state.last_cpu_usage_usec, 0)
            io_delta = max(write_bytes - state.last_write_bytes, 0)

            cpu_pct = (cpu_delta / (elapsed * 1_000_000.0)) * 100.0
            io_write_bytes_per_s = io_delta / elapsed

    state.last_cpu_usage_usec = cpu_usage_usec
    state.last_write_bytes = write_bytes
    state.last_sample_monotonic = now_monotonic

    return cpu_pct, io_write_bytes_per_s


class OrchestratorPublisher:
    def __init__(self, config: AgentConfig) -> None:
        self.config = config

    def publish(self, payload: dict[str, Any]) -> bool:
        encoded_node_id = urllib.parse.quote(self.config.node_id, safe="")
        url = f"{self.config.orchestrator_base_url}/internal/nodes/{encoded_node_id}/telemetry"

        try:
            http_json_request(
                method="POST",
                url=url,
                timeout_sec=self.config.http_timeout_sec,
                insecure_tls=self.config.insecure_tls,
                bearer_token=self.config.node_token,
                payload=payload,
            )
            return True
        except urllib.error.HTTPError as exc:
            message = exc.read().decode("utf-8", errors="replace")
            log(f"telemetry publish HTTP {exc.code}: {message}")
            return False
        except Exception as exc:  # noqa: BLE001
            log(f"telemetry publish failed: {exc}")
            return False


def run() -> None:
    config = AgentConfig.from_env()
    discoverer = WingsDiscoverer(config)
    cgroup_resolver = CgroupResolver()
    node_tracker = NodeMetricTracker()
    publisher = OrchestratorPublisher(config)

    server_states: dict[str, ServerRuntimeState] = {}
    discovered_servers: list[DiscoveredServer] = []

    next_discovery_at = 0.0
    next_send_at = 0.0
    send_backoff_sec = 1.0

    log(
        "node agent started "
        f"(node_id={config.node_id}, orchestrator={config.orchestrator_base_url}, wings={config.wings_base_url})"
    )

    while True:
        try:
            now_monotonic = time.monotonic()
            now_epoch = time.time()

            if now_monotonic >= next_discovery_at:
                discovered_servers = discoverer.discover_servers()
                known_ids = {server.server_id for server in discovered_servers}
                server_states = {
                    server_id: state
                    for server_id, state in server_states.items()
                    if server_id in known_ids
                }
                for server in discovered_servers:
                    server_states.setdefault(server.server_id, ServerRuntimeState(next_players_probe_epoch=0.0))

                next_discovery_at = now_monotonic + max(config.discovery_interval_sec, 5.0)
                log(f"discovered {len(discovered_servers)} running servers")

            node_metrics = node_tracker.sample()
            servers_payload: list[dict[str, Any]] = []

            for server in discovered_servers:
                state = server_states.setdefault(server.server_id, ServerRuntimeState(next_players_probe_epoch=0.0))
                cpu_pct, io_write_bps = sample_server_metrics(
                    server=server,
                    state=state,
                    resolver=cgroup_resolver,
                    now_monotonic=now_monotonic,
                )

                if now_epoch >= state.next_players_probe_epoch:
                    players = minecraft_players_online(
                        host=config.node_ip,
                        port=server.allocated_port,
                        timeout_sec=config.minecraft_ping_timeout_sec,
                    )
                    if players is not None or state.players_online is None:
                        state.players_online = players

                    state.next_players_probe_epoch = now_epoch + random.uniform(
                        config.players_interval_min_sec,
                        config.players_interval_max_sec,
                    )

                servers_payload.append(
                    {
                        "server_id": server.server_id,
                        "players_online": state.players_online,
                        "cpu_pct": round(cpu_pct, 3),
                        "io_write_bytes_per_s": round(io_write_bps, 3),
                    }
                )

            payload = {
                "node_id": config.node_id,
                "timestamp": datetime.now(timezone.utc).isoformat(),
                "node": {
                    "cpu_pct": round(node_metrics.cpu_pct, 3),
                    "iowait_pct": round(node_metrics.iowait_pct, 3),
                },
                "servers": servers_payload,
            }

            if now_monotonic >= next_send_at:
                sent = publisher.publish(payload)
                if sent:
                    send_backoff_sec = 1.0
                    next_send_at = now_monotonic
                else:
                    next_send_at = now_monotonic + send_backoff_sec
                    send_backoff_sec = min(send_backoff_sec * 2.0, max(config.send_backoff_max_sec, 1.0))

            time.sleep(
                random.uniform(
                    config.sample_interval_min_sec,
                    config.sample_interval_max_sec,
                )
            )
        except KeyboardInterrupt:
            log("node agent interrupted; exiting")
            raise
        except Exception as exc:  # noqa: BLE001
            log(f"unexpected loop error: {exc}")
            time.sleep(1.0)


if __name__ == "__main__":
    run()
