<?php

return [
    'indices' => [
        'mappings' => [
            'jav' => [
                'properties' => [
                    'actors' => [
                        'type' => 'keyword',
                    ],
                    'tags' => [
                        'type' => 'keyword',
                    ],
                    'title' => [
                        'type' => 'text',
                        'analyzer' => 'standard',
                    ],
                    'code' => [
                        'type' => 'keyword',
                    ],
                ],
            ],
            'actors' => [
                'properties' => [
                    'name' => [
                        'type' => 'text',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                            ],
                        ],
                    ],
                    'profile_primary_source' => [
                        'type' => 'keyword',
                    ],
                    'profile_attributes' => [
                        'type' => 'object',
                        'enabled' => true,
                    ],
                    'bio' => [
                        'type' => 'text',
                    ],
                ],
            ],
            'tags' => [
                'properties' => [
                    'name' => [
                        'type' => 'text',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'settings' => [
            'default' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ],
        ],
    ],
];
