<?php

return [
    'enabled' => env('JOB_TELEMETRY_ENABLED', true),

    'timer_ttl_seconds' => env('JOB_TELEMETRY_TIMER_TTL_SECONDS', 3600),
    'retention_days' => env('JOB_TELEMETRY_RETENTION_DAYS', 30),
    'auto_create_indexes' => env('JOB_TELEMETRY_AUTO_CREATE_INDEXES', true),

    'rate' => [
        'enabled' => env('JOB_TELEMETRY_RATE_ENABLED', true),
        'warning_per_second' => env('JOB_TELEMETRY_RATE_WARNING_PER_SECOND', 20),
        'critical_per_second' => env('JOB_TELEMETRY_RATE_CRITICAL_PER_SECOND', 40),
    ],

    'site_thresholds' => [
        // 'xcity.jp' => ['warning' => 15, 'critical' => 25],
    ],

    'site_map_by_job' => [
        'Modules\\JAV\\Jobs\\OnejavJob' => 'onejav',
        'Modules\\JAV\\Jobs\\OneFourOneJavJob' => '141jav',
        'Modules\\JAV\\Jobs\\FfjavJob' => 'ffjav',
        'Modules\\JAV\\Jobs\\DailySyncJob' => 'daily_sync',
        'Modules\\JAV\\Jobs\\TagsSyncJob' => 'tags_sync',
        'Modules\\JAV\\Jobs\\XcityKanaSyncJob' => 'xcity.jp',
        'Modules\\JAV\\Jobs\\XcityPersistIdolProfileJob' => 'xcity.jp',
        'Modules\\JAV\\Jobs\\XcitySyncActorSearchIndexJob' => 'xcity.jp',
    ],

    'site_fields' => ['site', 'source', 'domain', 'provider'],

    'url_fields' => [
        'url',
        'detailUrl',
        'detail_url',
        'seedUrl',
        'seed_url',
        'baseUrl',
        'base_url',
    ],
];
