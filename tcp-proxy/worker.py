#!/usr/bin/env python3
import hashlib
import json
import os
import signal
import subprocess
import sys
import tempfile
import time
from dataclasses import dataclass
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Dict, List, Optional

import requests


REQUIRED_ENV_VARS = ("ORCH_BASE_URL", "ORCH_TOKEN", "PROXY_ID", "PROXY_REGION")
KIND_GAME = "game"
KIND_SFTP = "sftp"
SUPPORTED_KINDS = (KIND_GAME, KIND_SFTP)
BINDINGS_ENDPOINT_PATHS = (
    "/api/internal/proxy-bindings",
    "/internal/proxy-bindings",
    "/api/regional-proxies/mappings",
)


@dataclass(frozen=True)
class Config:
    orch_base_url: str
    orch_token: str
    proxy_id: str
    proxy_region: str
    poll_interval_seconds: int
    game_listen_range: str
    sftp_listen_range: str
    haproxy_cfg: str
    haproxy_pidfile: str
    state_dir: str
    game_map_path: str
    sftp_map_path: str
    state_hash_path: str


@dataclass(frozen=True)
class Binding:
    kind: str
    listen_port: int
    target_host: str
    target_port: int
    updated_at: str
    source_index: int


class HaproxyController:
    def __init__(self, cfg_path: str, pidfile: str):
        self.cfg_path = cfg_path
        self.pidfile = pidfile
        self.process: Optional[subprocess.Popen] = None

    def _base_cmd(self) -> List[str]:
        return [
            "haproxy",
            "-W",
            "-db",
            "-f",
            self.cfg_path,
            "-p",
            self.pidfile,
        ]

    def start(self) -> None:
        if self.process is not None and self.process.poll() is None:
            return

        log("Starting HAProxy in master-worker mode")
        cmd = self._base_cmd()
        proc = subprocess.Popen(cmd)
        time.sleep(0.2)
        if proc.poll() is not None:
            raise RuntimeError(f"HAProxy failed to start, exit code {proc.returncode}")
        self.process = proc

    def reload(self) -> None:
        old_proc = self.process
        if old_proc is None or old_proc.poll() is not None:
            raise RuntimeError("Cannot reload HAProxy because no running process was found")

        old_pids = read_pidfile(self.pidfile)
        if not old_pids:
            old_pids = [str(old_proc.pid)]

        cmd = self._base_cmd() + ["-sf"] + old_pids
        log(f"Reloading HAProxy gracefully (old pids: {' '.join(old_pids)})")
        new_proc = subprocess.Popen(cmd)
        time.sleep(0.2)
        if new_proc.poll() is not None:
            raise RuntimeError(f"HAProxy reload command failed, exit code {new_proc.returncode}")

        self.process = new_proc
        try:
            old_proc.wait(timeout=10)
        except subprocess.TimeoutExpired:
            log("Previous HAProxy process did not exit within 10s after reload")

    def ensure_running(self) -> None:
        if self.process is None:
            raise RuntimeError("HAProxy process was not started")
        code = self.process.poll()
        if code is not None:
            raise RuntimeError(f"HAProxy process exited unexpectedly with code {code}")

    def stop(self) -> None:
        if self.process is None:
            return
        if self.process.poll() is not None:
            return

        log("Stopping HAProxy process")
        self.process.terminate()
        try:
            self.process.wait(timeout=10)
        except subprocess.TimeoutExpired:
            log("HAProxy did not terminate in time, killing process")
            self.process.kill()
            self.process.wait(timeout=5)


def now_utc() -> str:
    return datetime.now(timezone.utc).isoformat()


def log(message: str) -> None:
    print(f"{now_utc()} {message}", flush=True)


def read_pidfile(path: str) -> List[str]:
    try:
        raw = Path(path).read_text(encoding="utf-8").strip()
    except FileNotFoundError:
        return []
    except OSError as exc:
        log(f"Failed to read pidfile {path}: {exc}")
        return []

    if not raw:
        return []
    return raw.split()


def require_env(name: str) -> str:
    value = os.getenv(name, "").strip()
    if not value:
        raise ValueError(f"Missing required environment variable: {name}")
    return value


def load_config() -> Config:
    for env_name in REQUIRED_ENV_VARS:
        require_env(env_name)

    orch_base_url = require_env("ORCH_BASE_URL").rstrip("/")
    orch_token = require_env("ORCH_TOKEN")
    proxy_id = require_env("PROXY_ID")
    proxy_region = require_env("PROXY_REGION")

    poll_interval_raw = os.getenv("POLL_INTERVAL_SECONDS", "15").strip()
    try:
        poll_interval_seconds = int(poll_interval_raw)
    except ValueError as exc:
        raise ValueError("POLL_INTERVAL_SECONDS must be an integer") from exc
    if poll_interval_seconds < 1:
        raise ValueError("POLL_INTERVAL_SECONDS must be >= 1")

    game_listen_range = os.getenv("GAME_LISTEN_RANGE", "25565-25665").strip()
    sftp_listen_range = os.getenv("SFTP_LISTEN_RANGE", "30500-31000").strip()
    haproxy_cfg = os.getenv("HAPROXY_CFG", "/etc/haproxy/haproxy.cfg").strip()
    haproxy_pidfile = os.getenv("HAPROXY_PIDFILE", "/run/haproxy.pid").strip()
    state_dir = os.getenv("STATE_DIR", "/var/lib/proxy-sync").strip()

    game_map_path = "/etc/haproxy/game_port_to_backend.map"
    sftp_map_path = "/etc/haproxy/sftp_port_to_backend.map"
    state_hash_path = str(Path(state_dir) / "last_render.sha256")

    return Config(
        orch_base_url=orch_base_url,
        orch_token=orch_token,
        proxy_id=proxy_id,
        proxy_region=proxy_region,
        poll_interval_seconds=poll_interval_seconds,
        game_listen_range=game_listen_range,
        sftp_listen_range=sftp_listen_range,
        haproxy_cfg=haproxy_cfg,
        haproxy_pidfile=haproxy_pidfile,
        state_dir=state_dir,
        game_map_path=game_map_path,
        sftp_map_path=sftp_map_path,
        state_hash_path=state_hash_path,
    )


def build_bindings_endpoint_urls(base_url: str) -> List[str]:
    urls: List[str] = []
    normalized_base = base_url.rstrip("/")

    for path in BINDINGS_ENDPOINT_PATHS:
        resolved_path = path
        if normalized_base.endswith("/api") and path.startswith("/api/"):
            resolved_path = path[len("/api") :]
        url = f"{normalized_base}{resolved_path}"
        if url not in urls:
            urls.append(url)

    return urls


def extract_bindings_payload(payload: Any) -> List[dict]:
    if isinstance(payload, list):
        return payload

    if not isinstance(payload, dict):
        raise ValueError("Orchestrator response must be an object or list")

    candidates: List[Any] = [
        payload.get("bindings"),
        payload.get("mappings"),
    ]

    data = payload.get("data")
    if isinstance(data, dict):
        candidates.extend(
            [
                data.get("bindings"),
                data.get("mappings"),
            ]
        )

    for candidate in candidates:
        if isinstance(candidate, list):
            return candidate

    raise ValueError("Orchestrator response did not contain a bindings list")


def fetch_bindings(config: Config) -> List[dict]:
    headers = {
        "Authorization": f"Bearer {config.orch_token}",
        "X-Proxy-Id": config.proxy_id,
        "X-Proxy-Region": config.proxy_region,
    }
    urls = build_bindings_endpoint_urls(config.orch_base_url)
    last_404_url: Optional[str] = None

    for url in urls:
        resp = requests.get(url, headers=headers, timeout=10)
        if resp.status_code == 404:
            last_404_url = url
            continue

        resp.raise_for_status()
        payload = resp.json()
        return extract_bindings_payload(payload)

    raise RuntimeError(
        "No bindings endpoint found. Last attempted URL returned 404: "
        f"{last_404_url or 'unknown'}"
    )


def normalize_bindings(payload: List[dict]) -> Dict[str, List[Binding]]:
    per_kind: Dict[str, List[Binding]] = {KIND_GAME: [], KIND_SFTP: []}

    for idx, item in enumerate(payload):
        if not isinstance(item, dict):
            log(f"Skipping non-object binding at index {idx}")
            continue

        if not bool(item.get("enabled", False)):
            continue

        kind = str(item.get("kind", "")).strip()
        if kind not in SUPPORTED_KINDS:
            log(f"Skipping unsupported binding kind at index {idx}: {kind!r}")
            continue

        try:
            listen_port = int(item["listen_port"])
            target_port = int(item["target_port"])
            target_host = str(item["target_host"]).strip()
            updated_at = str(item.get("updated_at", ""))
        except (KeyError, TypeError, ValueError) as exc:
            log(f"Skipping malformed binding at index {idx}: {exc}")
            continue

        if listen_port < 1 or listen_port > 65535:
            log(f"Skipping binding with invalid listen_port at index {idx}: {listen_port}")
            continue
        if target_port < 1 or target_port > 65535:
            log(f"Skipping binding with invalid target_port at index {idx}: {target_port}")
            continue
        if not target_host:
            log(f"Skipping binding with empty target_host at index {idx}")
            continue
        if any(ch.isspace() for ch in target_host):
            log(f"Skipping binding with whitespace in target_host at index {idx}")
            continue

        per_kind[kind].append(
            Binding(
                kind=kind,
                listen_port=listen_port,
                target_host=target_host,
                target_port=target_port,
                updated_at=updated_at,
                source_index=idx,
            )
        )

    deduped: Dict[str, List[Binding]] = {KIND_GAME: [], KIND_SFTP: []}
    for kind in SUPPORTED_KINDS:
        sorted_kind = sorted(per_kind[kind], key=lambda b: b.listen_port)
        seen_ports = set()
        for binding in sorted_kind:
            key = binding.listen_port
            if key in seen_ports:
                log(
                    "Skipping duplicate binding for "
                    f"{kind}:{binding.listen_port} (updated_at={binding.updated_at})"
                )
                continue
            seen_ports.add(key)
            deduped[kind].append(binding)

    return deduped


def format_target_address(target_host: str, target_port: int) -> str:
    if target_host.startswith("[") and target_host.endswith("]"):
        return f"{target_host}:{target_port}"
    if ":" in target_host:
        return f"[{target_host}]:{target_port}"
    return f"{target_host}:{target_port}"


def render_map(bindings: List[Binding]) -> str:
    lines = [f"{b.listen_port} be_{b.kind}_{b.listen_port}" for b in bindings]
    if not lines:
        return ""
    return "\n".join(lines) + "\n"


def render_haproxy_cfg(config: Config, game_bindings: List[Binding], sftp_bindings: List[Binding]) -> str:
    lines: List[str] = [
        "global",
        "  log stdout format raw local0",
        "  maxconn 10000",
        "",
        "defaults",
        "  log global",
        "  mode tcp",
        "  option tcplog",
        "  timeout connect 5s",
        "  timeout client 1m",
        "  timeout server 1m",
        "",
        "frontend game_in",
        f"  bind *:{config.game_listen_range}",
        "  mode tcp",
        "  use_backend %[dst_port,map(/etc/haproxy/game_port_to_backend.map,be_blackhole)]",
        "",
        "frontend sftp_in",
        f"  bind *:{config.sftp_listen_range}",
        "  mode tcp",
        "  use_backend %[dst_port,map(/etc/haproxy/sftp_port_to_backend.map,be_blackhole)]",
        "",
        "backend be_blackhole",
        "  mode tcp",
        "  timeout connect 1s",
        "  timeout server 1s",
        "  server blackhole 127.0.0.1:1",
    ]

    for kind in SUPPORTED_KINDS:
        bindings = game_bindings if kind == KIND_GAME else sftp_bindings
        for b in bindings:
            lines.extend(
                [
                    "",
                    f"backend be_{kind}_{b.listen_port}",
                    "  mode tcp",
                    f"  server target {format_target_address(b.target_host, b.target_port)} check",
                ]
            )

    return "\n".join(lines) + "\n"


def compute_render_hash(haproxy_cfg: str, game_map: str, sftp_map: str) -> str:
    digest = hashlib.sha256()
    digest.update(b"haproxy.cfg\n")
    digest.update(haproxy_cfg.encode("utf-8"))
    digest.update(b"\ngame.map\n")
    digest.update(game_map.encode("utf-8"))
    digest.update(b"\nsftp.map\n")
    digest.update(sftp_map.encode("utf-8"))
    return digest.hexdigest()


def atomic_write(path: str, content: str) -> None:
    target = Path(path)
    target.parent.mkdir(parents=True, exist_ok=True)
    tmp_path: Optional[str] = None
    try:
        with tempfile.NamedTemporaryFile(
            mode="w",
            encoding="utf-8",
            dir=str(target.parent),
            prefix=f".{target.name}.tmp.",
            delete=False,
        ) as tmp:
            tmp.write(content)
            tmp.flush()
            os.fsync(tmp.fileno())
            tmp_path = tmp.name
        os.replace(tmp_path, str(target))
    except Exception:
        if tmp_path and os.path.exists(tmp_path):
            os.unlink(tmp_path)
        raise


def snapshot_files(paths: List[str]) -> Dict[str, Optional[str]]:
    snapshot: Dict[str, Optional[str]] = {}
    for path in paths:
        try:
            snapshot[path] = Path(path).read_text(encoding="utf-8")
        except FileNotFoundError:
            snapshot[path] = None
    return snapshot


def restore_snapshot(snapshot: Dict[str, Optional[str]]) -> None:
    for path, content in snapshot.items():
        if content is None:
            try:
                os.remove(path)
            except FileNotFoundError:
                pass
            continue
        atomic_write(path, content)


def validate_config(config_path: str) -> bool:
    result = subprocess.run(
        ["haproxy", "-c", "-f", config_path],
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        text=True,
        check=False,
    )
    if result.returncode == 0:
        return True

    stdout = result.stdout.strip()
    stderr = result.stderr.strip()
    if stdout:
        log(f"HAProxy validation stdout: {stdout}")
    if stderr:
        log(f"HAProxy validation stderr: {stderr}")
    return False


def render_outputs(config: Config, bindings: Dict[str, List[Binding]]) -> Dict[str, str]:
    game_bindings = bindings[KIND_GAME]
    sftp_bindings = bindings[KIND_SFTP]
    haproxy_cfg = render_haproxy_cfg(config, game_bindings, sftp_bindings)
    game_map = render_map(game_bindings)
    sftp_map = render_map(sftp_bindings)
    render_hash = compute_render_hash(haproxy_cfg, game_map, sftp_map)
    return {
        config.haproxy_cfg: haproxy_cfg,
        config.game_map_path: game_map,
        config.sftp_map_path: sftp_map,
        "__hash__": render_hash,
    }


def write_outputs(outputs: Dict[str, str], config: Config) -> None:
    atomic_write(config.haproxy_cfg, outputs[config.haproxy_cfg])
    atomic_write(config.game_map_path, outputs[config.game_map_path])
    atomic_write(config.sftp_map_path, outputs[config.sftp_map_path])


def persist_hash(config: Config, render_hash: str) -> None:
    Path(config.state_dir).mkdir(parents=True, exist_ok=True)
    atomic_write(config.state_hash_path, f"{render_hash}\n")


class Runner:
    def __init__(self, config: Config):
        self.config = config
        self.stop_requested = False
        self.controller = HaproxyController(config.haproxy_cfg, config.haproxy_pidfile)
        self.current_hash = ""

    def setup_signals(self) -> None:
        def _handler(signum, _frame):
            log(f"Received signal {signum}, shutting down")
            self.stop_requested = True

        signal.signal(signal.SIGINT, _handler)
        signal.signal(signal.SIGTERM, _handler)

    def fetch_and_render(self) -> Dict[str, str]:
        payload = fetch_bindings(self.config)
        normalized = normalize_bindings(payload)
        return render_outputs(self.config, normalized)

    def apply_outputs(self, outputs: Dict[str, str], startup: bool) -> bool:
        managed_paths = [
            self.config.haproxy_cfg,
            self.config.game_map_path,
            self.config.sftp_map_path,
        ]
        before = snapshot_files(managed_paths)

        try:
            write_outputs(outputs, self.config)
            if not validate_config(self.config.haproxy_cfg):
                log("Rendered HAProxy config is invalid, keeping existing configuration")
                restore_snapshot(before)
                return False

            if startup:
                self.controller.start()
            else:
                self.controller.reload()
        except Exception as exc:
            log(f"Failed to apply rendered outputs: {exc}")
            try:
                restore_snapshot(before)
            except Exception as restore_exc:
                log(f"Failed to restore previous configuration snapshot: {restore_exc}")
            return False

        self.current_hash = outputs["__hash__"]
        persist_hash(self.config, self.current_hash)
        return True

    def initial_sync(self) -> None:
        outputs = self.fetch_and_render()
        if not self.apply_outputs(outputs, startup=True):
            raise RuntimeError("Initial configuration apply failed")
        log(f"Initial sync complete (hash={self.current_hash})")

    def poll_loop(self) -> None:
        interval = self.config.poll_interval_seconds
        while not self.stop_requested:
            time.sleep(interval)
            if self.stop_requested:
                break

            try:
                self.controller.ensure_running()
            except Exception as exc:
                raise RuntimeError(str(exc)) from exc

            try:
                outputs = self.fetch_and_render()
            except Exception as exc:
                log(f"Failed to fetch bindings: {exc}")
                continue

            next_hash = outputs["__hash__"]
            if next_hash == self.current_hash:
                continue

            log(
                "Bindings changed, applying update "
                f"(old_hash={self.current_hash}, new_hash={next_hash})"
            )
            if self.apply_outputs(outputs, startup=False):
                log(f"Update applied successfully (hash={self.current_hash})")

    def run(self) -> int:
        self.setup_signals()
        try:
            self.initial_sync()
            self.poll_loop()
            return 0
        except Exception as exc:
            log(f"Fatal error: {exc}")
            return 1
        finally:
            self.controller.stop()


def main() -> int:
    try:
        config = load_config()
    except Exception as exc:
        log(f"Configuration error: {exc}")
        return 1

    log(
        "Starting proxy worker with config: "
        + json.dumps(
            {
                "orch_base_url": config.orch_base_url,
                "proxy_id": config.proxy_id,
                "proxy_region": config.proxy_region,
                "poll_interval_seconds": config.poll_interval_seconds,
                "game_listen_range": config.game_listen_range,
                "sftp_listen_range": config.sftp_listen_range,
                "haproxy_cfg": config.haproxy_cfg,
                "haproxy_pidfile": config.haproxy_pidfile,
                "state_dir": config.state_dir,
            }
        )
    )

    runner = Runner(config)
    return runner.run()


if __name__ == "__main__":
    sys.exit(main())
