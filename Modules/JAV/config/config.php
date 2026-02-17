<?php

return [
    'name' => 'JAV',
    'show_cover' => env('SHOW_COVER', false),
    'idol_queue' => env('JAV_IDOL_QUEUE', 'xcity'),
    'content_queues' => [
        'onejav' => env('JAV_ONEJAV_QUEUE', 'onejav'),
        '141jav' => env('JAV_141_QUEUE', '141'),
        'ffjav' => env('JAV_FFJAV_QUEUE', 'jav'),
        'missav' => env('JAV_MISSAV_QUEUE', 'missav'),
    ],
    'profile_source_priority' => [
        'xcity' => 100,
    ],
    'missav' => [
        'tmp_dir' => env('MISSAV_TMP_DIR', 'app/tmp/missav'),
        'schedule_batch' => (int) env('MISSAV_SCHEDULE_BATCH', 5),
        'playwright' => [
            'headless' => env('MISSAV_PLAYWRIGHT_HEADLESS', false),
            'timeout_ms' => env('MISSAV_PLAYWRIGHT_TIMEOUT_MS', 45000),
            'args' => env('MISSAV_PLAYWRIGHT_ARGS', '--no-sandbox'),
            'wait_until' => env('MISSAV_PLAYWRIGHT_WAIT_UNTIL', 'domcontentloaded'),
        ],
    ],
];
