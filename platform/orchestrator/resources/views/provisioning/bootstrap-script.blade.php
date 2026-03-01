#!/usr/bin/env bash
set -euo pipefail

log() {
  printf '[node-bootstrap] %s\n' "$*"
}

if [[ "${EUID:-0}" -ne 0 ]]; then
  echo "This script must run as root (for example: curl ... | sudo bash)." >&2
  exit 1
fi

NODE_ID='{!! $nodeIdQuoted !!}'
NODE_TOKEN='{!! $nodeTokenQuoted !!}'
NODE_IP='{!! $nodeIpQuoted !!}'
ORCHESTRATOR_BASE_URL='{!! $orchestratorBaseUrlQuoted !!}'
WINGS_CONFIG_B64='{!! $wingsConfigB64Quoted !!}'
WINGS_URL_AMD64='{!! $wingsUrlAmd64Quoted !!}'
WINGS_URL_ARM64='{!! $wingsUrlArm64Quoted !!}'

if ! command -v curl >/dev/null 2>&1; then
  apt-get update -y
  apt-get install -y curl ca-certificates
fi

if ! command -v docker >/dev/null 2>&1; then
  log "Installing Docker..."
  curl -fsSL https://get.docker.com | sh
else
  log "Docker already installed."
fi

systemctl enable --now docker

ARCH="$(uname -m)"
case "${ARCH}" in
  x86_64|amd64)
    WINGS_BINARY_URL="${WINGS_URL_AMD64}"
    ;;
  aarch64|arm64)
    WINGS_BINARY_URL="${WINGS_URL_ARM64}"
    ;;
  *)
    echo "Unsupported architecture: ${ARCH}" >&2
    exit 1
    ;;
esac

log "Installing Wings from ${WINGS_BINARY_URL}..."
curl -fsSL "${WINGS_BINARY_URL}" -o /usr/local/bin/wings
chmod 0755 /usr/local/bin/wings

install -d -m 0755 /etc/pterodactyl
install -d -m 0755 /var/run/wings
printf '%s' "${WINGS_CONFIG_B64}" | base64 --decode > /etc/pterodactyl/config.yml

cat >/etc/systemd/system/wings.service <<'SERVICE'
[Unit]
Description=Pterodactyl Wings Daemon
After=docker.service
Requires=docker.service

[Service]
User=root
WorkingDirectory=/etc/pterodactyl
LimitNOFILE=4096
PIDFile=/var/run/wings/daemon.pid
ExecStart=/usr/local/bin/wings
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
SERVICE

if ! command -v python3 >/dev/null 2>&1; then
  log "Installing Python 3 runtime..."
  apt-get update -y
  apt-get install -y python3
fi

install -d -m 0755 /opt/intera/orchestrator-monitor

{!! $monitorInstallBlock !!}

chmod 0755 /opt/intera/orchestrator-monitor/main.py

cat >/etc/orchestrator-node-monitor.env <<EOF
NODE_ID=${NODE_ID}
NODE_TOKEN=${NODE_TOKEN}
NODE_IP=${NODE_IP}
ORCHESTRATOR_BASE_URL=${ORCHESTRATOR_BASE_URL}
WINGS_BASE_URL=http://127.0.0.1:8080
EOF
chmod 0600 /etc/orchestrator-node-monitor.env

cat >/etc/systemd/system/orchestrator-node-monitor.service <<'SERVICE'
[Unit]
Description=Intera Orchestrator Node Monitor
After=network-online.target wings.service
Wants=network-online.target
Requires=wings.service

[Service]
Type=simple
User=root
Group=root
EnvironmentFile=/etc/orchestrator-node-monitor.env
WorkingDirectory=/opt/intera/orchestrator-monitor
ExecStart=/usr/bin/env python3 /opt/intera/orchestrator-monitor/main.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
SERVICE

systemctl daemon-reload
systemctl enable --now wings.service
systemctl enable --now orchestrator-node-monitor.service
systemctl restart wings.service
systemctl restart orchestrator-node-monitor.service

log "Bootstrap complete."
log "Wings and orchestrator monitor are installed and running."
