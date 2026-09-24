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
];
