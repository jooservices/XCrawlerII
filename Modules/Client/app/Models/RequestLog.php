<?php

namespace Modules\Client\Models;

use MongoDB\Laravel\Eloquent\Model;

class RequestLog extends Model
{
    protected $connection = 'mongodb';

    protected string $collection = 'request_logs';

    protected $fillable = [
        'ip',
        'method',
        'endpoint',
        'payload',
        'status_code',
        'body',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
