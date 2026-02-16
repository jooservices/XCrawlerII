<?php

return [
    'name' => 'JAV',
    'show_cover' => env('SHOW_COVER', false),
    'idol_queue' => env('JAV_IDOL_QUEUE', 'xcity'),
    'content_queues' => [
        'onejav' => env('JAV_ONEJAV_QUEUE', 'onejav'),
        '141jav' => env('JAV_141_QUEUE', '141'),
        'ffjav' => env('JAV_FFJAV_QUEUE', 'jav'),
    ],
    'profile_source_priority' => [
        'xcity' => 100,
    ],
];
