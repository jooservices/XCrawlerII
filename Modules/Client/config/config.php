<?php

return [
    'name' => 'Client',
    'cache' => [
        'enable' => env('CLIENT_ENABLE_CACHE', true),
        'interval' => env('CLIENT_CACHE_INTERVAL', 60),
    ],
];
