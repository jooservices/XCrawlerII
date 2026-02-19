<?php

return [
    'enabled' => env('ANALYTICS_EVENTS_ENABLED', false),
    'redis_prefix' => env('ANALYTICS_REDIS_PREFIX', 'anl:counters'),
    'flush_interval_minutes' => (int) env('ANALYTICS_FLUSH_INTERVAL', 1),
    'rate_limit_per_minute' => (int) env('ANALYTICS_RATE_LIMIT', 60),
];
