<?php

namespace Modules\Core\Models\Mongo\Analytics;

use MongoDB\Laravel\Eloquent\Model;

class AnalyticsEntityTotals extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'analytics_entity_totals';

    protected $fillable = [
        'domain',
        'entity_type',
        'entity_id',
        'view',
        'download',
    ];

    protected $casts = [
        'view' => 'integer',
        'download' => 'integer',
    ];
}
