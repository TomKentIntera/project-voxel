FROM debian:bookworm-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        haproxy \
        python3 \
        python3-requests \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

ENV POLL_INTERVAL_SECONDS=15 \
    GAME_LISTEN_RANGE=25565-25665 \
    SFTP_LISTEN_RANGE=30500-31000 \
    HAPROXY_CFG=/etc/haproxy/haproxy.cfg \
    HAPROXY_PIDFILE=/run/haproxy.pid \
    STATE_DIR=/var/lib/proxy-sync

COPY worker.py /app/worker.py
COPY entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh \
    && mkdir -p /etc/haproxy /run /var/lib/proxy-sync

ENTRYPOINT ["/entrypoint.sh"]
