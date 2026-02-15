<?php

return [
    'name' => 'JAV',
    'show_cover' => env('SHOW_COVER', false),
    'idol_queue' => env('JAV_IDOL_QUEUE', 'jav-idol'),
    'profile_source_priority' => [
        'xcity' => 100,
    ],
];
