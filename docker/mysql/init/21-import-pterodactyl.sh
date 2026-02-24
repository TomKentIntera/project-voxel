#!/bin/sh
set -eu

# MySQL image runs scripts in /docker-entrypoint-initdb.d only on first boot
# (when the data directory is empty). Import the panel seed dump into the
# dedicated pterodactyl schema created by 01-create-databases.sql.
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" pterodactyl < /docker-entrypoint-initdb.d/20-pterodactyl.sql

