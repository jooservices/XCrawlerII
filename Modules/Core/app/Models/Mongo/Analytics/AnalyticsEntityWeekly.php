<?php

namespace Modules\Core\Models\Mongo\Analytics;

use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $domain
 * @property string $entity_type
 * @property string $entity_id
 * @property string $week
 * @property int $view
 * @property int $download
 */
class AnalyticsEntityWeekly extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'analytics_entity_weekly';

    protected $fillable = [
        'domain',
        'entity_type',
        'entity_id',
        'week',
        'view',
        'download',
    ];

    protected $casts = [
        'view' => 'integer',
        'download' => 'integer',
        'week' => 'string',
    ];
}
