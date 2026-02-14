<?php

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActorProfileSource extends Model
{
    protected $table = 'actor_profile_sources';

    protected $fillable = [
        'actor_id',
        'source',
        'source_actor_id',
        'source_url',
        'source_cover',
        'payload',
        'is_primary',
        'fetched_at',
        'synced_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_primary' => 'boolean',
        'fetched_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'actor_id');
    }
}
