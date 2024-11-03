<?php

return [
    'name' => 'Udemy',
    'client' => [
        'base_uri' => 'https://fpt-software.udemy.com/',
    ],
    'notifications' => [
        'telegram' => [
            'chat_id' => env('UDEMY_NOTIFICATION_TELEGRAM_CHAT_ID'),
        ]
    ]
];
