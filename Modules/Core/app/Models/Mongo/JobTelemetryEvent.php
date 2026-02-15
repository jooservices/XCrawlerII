<?php

namespace Modules\Core\Models\Mongo;

use MongoDB\Laravel\Eloquent\Model;

class JobTelemetryEvent extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'job_events';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'timestamp' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'second_bucket' => 'datetime',
        'expire_at' => 'datetime',
        'attempt' => 'integer',
        'duration_ms' => 'integer',
        'jobs_per_second' => 'integer',
        'error_code' => 'integer',
        'timeout_ms_observed' => 'integer',
        'payload_meta' => 'array',
    ];
}
