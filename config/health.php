<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Disk Space Thresholds
    |--------------------------------------------------------------------------
    |
    | The health check reports the filesystem as unhealthy when the
    | percentage of free space on the storage volume drops below
    | `disk_critical_percent`. This is a hard-fail signal — once the
    | disk is truly full, Laravel can no longer write sessions, cache,
    | logs, or generated PDFs, so an early warning is worth surfacing
    | in the monitoring dashboard before the app starts returning 500s.
    |
    | Default: 5%.
    |
    */
    'disk_critical_percent' => (float) env('HEALTH_DISK_CRITICAL_PERCENT', 5),

    /*
    |--------------------------------------------------------------------------
    | Backup Freshness Threshold
    |--------------------------------------------------------------------------
    |
    | The health check reads the mtime of the freshness marker that the
    | backup sidecar touches after every successful dump. If the marker
    | is older than this many hours, the app is reported unhealthy — a
    | stale backup is a silent data-loss risk that no other probe
    | catches.
    |
    | Default: 25 hours, giving a 1-hour grace window beyond the
    | nightly 24-hour backup cadence. Set to 0 to disable the check
    | entirely (e.g. in development where there is no backup sidecar).
    |
    */
    'backup_max_age_hours' => (int) env('HEALTH_BACKUP_MAX_AGE_HOURS', 25),

    /*
    | Path to the freshness marker written by the backup sidecar.
    | Read-only bind mount from the host's ./backups directory.
    |
    | In local development this file usually does not exist, and the
    | check is automatically skipped — see HealthController.
    */
    'backup_marker_path' => storage_path('app/backups/.last-success'),
];
