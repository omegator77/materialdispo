#!/bin/bash
#
# Nightly MySQL backup for the materialdispo Docker setup.
# Dumps the `laravel` database from the `laravel-mysql` container, gzips it
# with a timestamped filename, and prunes dumps older than RETENTION_DAYS.
#
# Intended to run on the host (outside Docker) via crontab, independent of
# the app container, so backups keep working even if the app itself breaks.
# Explicitly invoked via `bash` so a missing executable bit (e.g. lost on a
# checkout from a Windows dev machine, where git filemode is off) can't stop
# the nightly backup — that outage silently killed backups for ~8 days once:
#   15 3 * * * /bin/bash /home/noicebard/docker-projects/laravel-app/app/scripts/backup-db.sh >> /home/noicebard/docker-projects/laravel-app/backups/backup.log 2>&1
#
# This script lives inside the app/ git checkout (app/scripts/backup-db.sh),
# but writes backups one level up, next to docker-compose.yml — never inside
# the git working copy, so they can't be touched by git operations on app/
# and never risk being committed.
#
# The DB password is never passed on this script's command line — it's read
# from the mysql container's own MYSQL_PASSWORD env var via `docker exec`.

set -euo pipefail

MYSQL_CONTAINER="laravel-mysql"
MYSQL_USER="laravel"
DB_NAME="laravel"
BACKUP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)/backups"
RETENTION_DAYS=90

TIMESTAMP="$(date +%Y-%m-%d_%H%M%S)"
FILENAME="laravel_${TIMESTAMP}.sql.gz"

mkdir -p "$BACKUP_DIR"

docker exec "$MYSQL_CONTAINER" sh -c "exec mysqldump -u ${MYSQL_USER} -p\"\$MYSQL_PASSWORD\" --single-transaction --quick --no-tablespaces ${DB_NAME}" \
    | gzip > "$BACKUP_DIR/$FILENAME"

find "$BACKUP_DIR" -name 'laravel_*.sql.gz' -type f -mtime +"$RETENTION_DAYS" -delete

echo "$(date '+%Y-%m-%d %H:%M:%S') Backup erstellt: $FILENAME ($(du -h "$BACKUP_DIR/$FILENAME" | cut -f1))"
