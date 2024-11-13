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
        'max_process' => env('UDEMY_HORIZON_MAX_PROCESS', 2),
        'memory_limit' => env('UDEMY_HORIZON_MEMORY', 512),
    ],
];
