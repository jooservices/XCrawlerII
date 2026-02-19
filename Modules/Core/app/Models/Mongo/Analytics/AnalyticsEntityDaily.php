<?php

namespace Modules\Core\Models\Mongo\Analytics;

use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $domain
 * @property string $entity_type
 * @property string $entity_id
 * @property string $date
 * @property int $view
 * @property int $download
 */
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
