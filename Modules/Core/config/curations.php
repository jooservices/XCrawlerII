<?php

return [
    'types' => [
        'featured',
        'trending',
        'editor_pick',
    ],

    'item_models' => [
        'jav' => \Modules\JAV\Models\Jav::class,
        'actor' => \Modules\JAV\Models\Actor::class,
        'tag' => \Modules\JAV\Models\Tag::class,
    ],
];
