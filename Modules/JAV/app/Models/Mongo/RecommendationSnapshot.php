<?php

namespace Modules\JAV\Models\Mongo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class RecommendationSnapshot extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';

    protected $collection = 'recommendation_snapshots';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'generated_at',
        'payload',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'generated_at' => 'datetime',
        'payload' => 'array',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\RecommendationSnapshotFactory::new();
    }
}
