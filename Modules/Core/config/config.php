<?php

return [
    'name' => 'Core',
    'logging' => [
        'preview_bytes' => (int) env('XCRAWLER_LOG_PREVIEW_BYTES', 8192),
    ],
    'client' => [
        'timeout_sec' => (int) env('XCRAWLER_CLIENT_TIMEOUT', 20),
        'connect_timeout_sec' => (int) env('XCRAWLER_CLIENT_CONNECT_TIMEOUT', 8),
        'max_attempts' => (int) env('XCRAWLER_CLIENT_MAX_ATTEMPTS', 3),
        'cache_ttl_sec' => (int) env('XCRAWLER_CLIENT_CACHE_TTL', 300),
        'cache_store' => (string) env('CACHE_STORE', 'database'),
    ],
    'reactions' => [
        'allowed_types' => [
            'like',
            'dislike',
            'love',
            'haha',
            'wow',
            'sad',
            'angry',
        ],
    ],
];
