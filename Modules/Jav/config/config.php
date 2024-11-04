<?php

return [
    'name' => 'Jav',
    'onejav' => [
        'base_uri' => env('ONEJAV_BASE_URI', 'https://onejav.com/'),
        'cache_interval' => env('ONEJAV_CACHE_INTERVAL', 3600), // 1 hr,
    ],
    'horizon' => [
        'memory_limit' => env('ONEJAV_HORIZON_MEMORY', 1024),
        'max_process' => env('ONEJAV_HORIZON_MAX_PROCESS', 1),
    ],
];
