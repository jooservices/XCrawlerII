<?php

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrawlerResponseCache extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'type',
        'url',
        'cache_key',
        'status_code',
        'headers',
        'body',
        'fetched_at',
        'expires_at',
    ];

    protected $casts = [
        'fetched_at' => 'datetime',
        'expires_at' => 'datetime',
        'status_code' => 'integer',
    ];
}
