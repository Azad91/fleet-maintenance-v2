#!/bin/sh
# =============================================================================
# Backup sidecar entrypoint.
#
# Installs a single crontab entry and runs crond in the foreground. This is
# the standard Alpine pattern — no supervisor, no systemd, one process that
# stays in the foreground so Docker's restart policy can manage the container.
#
# Environment (from .env.production.db):
#   POSTGRES_USER, POSTGRES_PASSWORD, POSTGRES_DB
#
# Optional:
#   BACKUP_RETENTION_DAYS  — how many days of backups to keep (default 14)
#   BACKUP_HOUR            — hour of the day to run (default 02)
# =============================================================================
set -e

BACKUP_HOUR="${BACKUP_HOUR:-02}"
BACKUP_RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-14}"

# Persist these to /etc/environment so crond's child shell inherits them.
# Cron does NOT source any shell profile, so anything the backup script
# reads must live here.
cat > /etc/environment <<EOF
POSTGRES_USER=${POSTGRES_USER}
POSTGRES_PASSWORD=${POSTGRES_PASSWORD}
POSTGRES_DB=${POSTGRES_DB}
BACKUP_RETENTION_DAYS=${BACKUP_RETENTION_DAYS}
EOF

# Install the crontab. 02:00 by default, but operators can override the
# hour via BACKUP_HOUR without touching the repo.
cat > /etc/crontabs/root <<EOF
0 ${BACKUP_HOUR} * * * /usr/local/bin/backup-run.sh >> /var/log/backup.log 2>&1
EOF

echo "[$(date)] Backup sidecar starting — cron hour=${BACKUP_HOUR}, retention=${BACKUP_RETENTION_DAYS}d"

# crond needs a writable log directory. -l 2 = log level info.
touch /var/log/backup.log
exec crond -f -l 2
