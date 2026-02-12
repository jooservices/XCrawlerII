<?php

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Jav extends Model
{
    protected $table = 'jav';

    protected $fillable = [
        'item_id',
        'code',
        'title',
        'url',
        'image',
        'date',
        'size',
        'description',
        'download',
        'source',
    ];

    protected $casts = [
        'date' => 'datetime',
        'size' => 'float',
    ];

    public function actors(): BelongsToMany
    {
        return $this->belongsToMany(Actor::class, 'jav_actor');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'jav_tag');
    }
}
