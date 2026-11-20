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
# Sicherheit/Robustheit:
#  - Das DB-Passwort steht in keiner Kommandozeile (weder hier noch im
#    Container-`ps`): mysqldump bekommt es über MYSQL_PWD, gelesen aus der
#    MYSQL_PASSWORD-Umgebung des mysql-Containers.
#  - umask 077: Dump (enthält Passwort-Hashes und personenbezogene Daten) und
#    Backup-Ordner sind nur für den ausführenden Benutzer lesbar.
#  - Der Dump wird in eine .tmp-Datei geschrieben, geprüft (gzip -t und die
#    "Dump completed"-Schlusszeile von mysqldump) und erst dann atomar
#    umbenannt. Ein fehlgeschlagener/abgeschnittener Dump hinterlässt nie eine
#    Datei, die wie ein gültiges Backup aussieht.
#  - Alte Backups werden nur nach einem erfolgreichen Lauf gelöscht.
#  - Optional: HEALTHCHECK_URL (z. B. healthchecks.io) wird nach Erfolg
#    angepingt; bleibt der Ping aus, schlägt der Dienst Alarm.
#
# Restore: siehe docs/betrieb.md ("Backup einspielen").
#
# Für Tests/Sonderfälle überschreibbar: BACKUP_DIR, MYSQL_CONTAINER, MYSQL_USER,
# DB_NAME, RETENTION_DAYS.

set -euo pipefail
umask 077

MYSQL_CONTAINER="${MYSQL_CONTAINER:-laravel-mysql}"
MYSQL_USER="${MYSQL_USER:-laravel}"
DB_NAME="${DB_NAME:-laravel}"
BACKUP_DIR="${BACKUP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)/backups}"
RETENTION_DAYS="${RETENTION_DAYS:-90}"

TIMESTAMP="$(date +%Y-%m-%d_%H%M%S)"
FILENAME="laravel_${TIMESTAMP}.sql.gz"

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

TMP_FILE="$BACKUP_DIR/.${FILENAME}.tmp"
trap 'rm -f "$TMP_FILE"' EXIT

# Benutzer und Datenbankname kommen als Positionsparameter ($0/$1) in die
# Container-Shell, das Passwort aus deren eigener Umgebung.
docker exec "$MYSQL_CONTAINER" sh -c \
    'MYSQL_PWD="$MYSQL_PASSWORD" exec mysqldump -u "$0" --single-transaction --quick --no-tablespaces "$1"' \
    "$MYSQL_USER" "$DB_NAME" \
    | gzip > "$TMP_FILE"

if ! gzip -t "$TMP_FILE" || ! gzip -dc "$TMP_FILE" | tail -n 5 | grep -q -- '-- Dump completed'; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') FEHLER: Dump unvollständig oder leer, Backup verworfen." >&2
    exit 1
fi

mv "$TMP_FILE" "$BACKUP_DIR/$FILENAME"

find "$BACKUP_DIR" -name 'laravel_*.sql.gz' -type f -mtime +"$RETENTION_DAYS" -delete

echo "$(date '+%Y-%m-%d %H:%M:%S') Backup erstellt: $FILENAME ($(du -h "$BACKUP_DIR/$FILENAME" | cut -f1))"

if [ -n "${HEALTHCHECK_URL:-}" ]; then
    curl -fsS -m 10 --retry 3 -o /dev/null "$HEALTHCHECK_URL" || echo "WARNUNG: Healthcheck-Ping fehlgeschlagen." >&2
fi
