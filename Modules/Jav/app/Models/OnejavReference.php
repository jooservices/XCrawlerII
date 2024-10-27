<?php

namespace Modules\Jav\Models;

use MongoDB\Laravel\Eloquent\Model;

class OnejavReference extends Model
{
    protected $table = 'onejav';
    protected $connection = 'mongodb';
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'url',
        'cover',
        'dvd_id',
        'size',
        'release_date',
        'genres',
        'description',
        'performers',
        'torrent',
        'gallery',
    ];

    protected $casts = [
        'url' => 'string',
        'cover' => 'string',
        'dvd_id' => 'string',
        'size' => 'float',
        'release_date' => 'date',
        'genres' => 'array',
        'description' => 'string',
        'performers' => 'array',
        'torrent' => 'string',
        'gallery' => 'array',
    ];
}
