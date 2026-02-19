<?php

namespace Modules\Core\Models\Mongo\Analytics;

use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $domain
 * @property string $entity_type
 * @property string $entity_id
 * @property string $year
 * @property int $view
 * @property int $download
 */
class AnalyticsEntityYearly extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'analytics_entity_yearly';

    protected $fillable = [
        'domain',
        'entity_type',
        'entity_id',
        'year',
        'view',
        'download',
    ];

    protected $casts = [
        'view' => 'integer',
        'download' => 'integer',
        'year' => 'string',
    ];
}
