<?php

namespace Modules\Core\Models\Mongo\Analytics;

use MongoDB\Laravel\Eloquent\Model;

class AnalyticsEntityDaily extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'analytics_entity_daily';

    protected $fillable = [
        'domain',
        'entity_type',
        'entity_id',
        'date',
        'view',
        'download',
    ];

    protected $casts = [
        'view' => 'integer',
        'download' => 'integer',
        'date' => 'string',
    ];
}
