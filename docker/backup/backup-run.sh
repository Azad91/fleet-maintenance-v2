#!/bin/sh
# =============================================================================
# Single-shot backup script — invoked by crond once per day.
#
# Flow:
#   1. pg_dump the database to a timestamped .sql file.
#   2. Compress with gzip -9.
#   3. Write a freshness marker (/backups/.last-success) that the app's
#      /health endpoint reads to detect a stale backup.
#   4. Delete backups older than BACKUP_RETENTION_DAYS.
#
# On failure: remove the partial dump, log it, and exit non-zero so
# crond records the failure.
# =============================================================================
set -eu

TS="$(date +%Y-%m-%d_%H-%M-%S)"
OUT="/backups/fleet_${TS}.sql.gz"
TMP="/backups/.fleet_${TS}.sql.partial"
MARKER="/backups/.last-success"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-14}"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"; }

log "Starting backup -> ${OUT}"

# Dump directly to a partial file so a crash halfway through never leaves
# a truncated .sql.gz that a restore script would happily try to load.
if ! PGPASSWORD="${POSTGRES_PASSWORD}" pg_dump \
        -h postgres \
        -U "${POSTGRES_USER}" \
        -d "${POSTGRES_DB}" \
        --no-owner \
        --no-privileges \
        --clean \
        --if-exists \
        > "${TMP}"; then
    log "ERROR: pg_dump failed"
    rm -f "${TMP}"
    exit 1
fi

# Compress and remove the raw partial in one atomic-ish step.
if ! gzip -9 < "${TMP}" > "${OUT}"; then
    log "ERROR: gzip failed"
    rm -f "${TMP}" "${OUT}"
    exit 1
fi

rm -f "${TMP}"

SIZE="$(du -h "${OUT}" | cut -f1)"
log "Backup OK — ${OUT} (${SIZE})"

# Freshness marker — the health endpoint reads mtime of this file.
touch "${MARKER}"

# Retention sweep. Uses -mtime which is measured in 24-hour units by
# POSIX, so a value of 14 means "strictly more than 14*24 hours old".
DELETED=$(find /backups -maxdepth 1 -name 'fleet_*.sql.gz' -mtime +"${RETENTION_DAYS}" -print -delete | wc -l)
log "Retention cleanup done — removed ${DELETED} file(s) older than ${RETENTION_DAYS} days"

exit 0
