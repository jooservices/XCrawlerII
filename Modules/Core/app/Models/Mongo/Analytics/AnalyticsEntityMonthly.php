<?php

namespace Modules\Core\Models\Mongo\Analytics;

use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $domain
 * @property string $entity_type
 * @property string $entity_id
 * @property string $month
 * @property int $view
 * @property int $download
 */
class AnalyticsEntityMonthly extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'analytics_entity_monthly';

    protected $fillable = [
        'domain',
        'entity_type',
        'entity_id',
        'month',
        'view',
        'download',
    ];

    protected $casts = [
        'view' => 'integer',
        'download' => 'integer',
        'month' => 'string',
    ];
}
