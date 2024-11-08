<?php

return [
    'name' => 'Udemy',
    'client' => [
        'base_uri' => 'https://fpt-software.udemy.com/',
    ],
    'notifications' => [
        'enabled' => env('UDEMY_NOTIFICATION_ENABLED', false),
        'telegram' => [
            'chat_id' => env('UDEMY_NOTIFICATION_TELEGRAM_CHAT_ID'),
        ],
    ],
    'horizon' => [
        'memory_limit' => env('UDEMY_HORIZON_MEMORY', 1024),
        'max_process' => env('UDEMY_HORIZON_MAX_PROCESS', 1),
    ],
];
