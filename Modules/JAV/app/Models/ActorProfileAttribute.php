<?php

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActorProfileAttribute extends Model
{
    protected $table = 'actor_profile_attributes';

    protected $fillable = [
        'actor_id',
        'source',
        'kind',
        'value_string',
        'value_number',
        'value_date',
        'value_label',
        'raw_value',
        'is_primary',
        'synced_at',
    ];

    protected $casts = [
        'value_number' => 'decimal:2',
        'value_date' => 'date',
        'is_primary' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'actor_id');
    }
}
