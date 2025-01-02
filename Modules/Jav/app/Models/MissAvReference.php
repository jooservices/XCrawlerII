<?php

namespace Modules\Jav\Models;

use MongoDB\Laravel\Eloquent\Model;

class MissAvReference extends Model
{
    protected $table = 'missav';

    protected $connection = 'mongodb';

    protected $fillable = [
        'url',
        'meta_title',
        'code',
        'title',
        'actress',
        'actor',
        'director',
        'studio',
        'label',
        'genre',
        'cover',
        'release_date',
    ];

    protected $casts = [
        'url' => 'string',
        'meta_title' => 'string',
        'code' => 'string',
        'title' => 'string',
        'actress' => 'array',
        'actor' => 'array',
        'director' => 'array',
        'studio' => 'array',
        'label' => 'array',
        'genre' => 'array',
        'cover' => 'string',
        'release_date' => 'date',
    ];
}
