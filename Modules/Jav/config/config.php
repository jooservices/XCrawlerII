<?php

return [
    'name' => 'Jav',
    'onejav' => [
        'base_uri' => env('ONEJAV_BASE_URI', 'https://onejav.com/'),
        'cache_interval' => env('ONEJAV_CACHE_INTERVAL', 3600), // 1 hr,
    ],
];
