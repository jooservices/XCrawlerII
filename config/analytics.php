<?php

return [
    'redis_prefix' => env('ANALYTICS_REDIS_PREFIX', 'anl:counters'),
    'flush_interval_minutes' => (int) env('ANALYTICS_FLUSH_INTERVAL', 1),
    'rate_limit_per_minute' => (int) env('ANALYTICS_RATE_LIMIT', 60),
    'schedule_flush' => (bool) env('ANALYTICS_SCHEDULE_FLUSH', true),
    'evidence' => [
        'schedule_daily' => (bool) env('ANALYTICS_EVIDENCE_SCHEDULE_DAILY', true),
        'daily_at' => env('ANALYTICS_EVIDENCE_DAILY_AT', '00:10'),
        'days' => (int) env('ANALYTICS_EVIDENCE_DAYS', 7),
        'limit' => (int) env('ANALYTICS_EVIDENCE_LIMIT', 500),
        'output_dir' => env('ANALYTICS_EVIDENCE_OUTPUT_DIR', 'logs/analytics/evidence'),
        'archive' => (bool) env('ANALYTICS_EVIDENCE_ARCHIVE', true),
        'rollback' => (bool) env('ANALYTICS_EVIDENCE_ROLLBACK', true),
    ],
];
