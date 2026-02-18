<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;

class FeaturedItem extends Model
{
    protected $fillable = [
        'item_type',
        'item_id',
        'group',
        'rank',
        'is_active',
        'featured_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'featured_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Dynamic relationship based on item_type
    public function item()
    {
        return $this->morphTo('item', 'item_type', 'item_id');
    }
}
