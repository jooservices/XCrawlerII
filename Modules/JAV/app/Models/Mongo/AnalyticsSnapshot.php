<?php

namespace Modules\JAV\Models\Mongo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class AnalyticsSnapshot extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';

    protected $collection = 'analytics_snapshots';

    public $timestamps = false;

    protected $fillable = [
        'days',
        'generated_at',
        'payload',
    ];

    protected $casts = [
        'days' => 'integer',
        'generated_at' => 'datetime',
        'payload' => 'array',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\AnalyticsSnapshotFactory::new();
    }
}
