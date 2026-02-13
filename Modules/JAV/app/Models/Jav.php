<?php

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;

class Jav extends Model
{
    use Searchable;

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

    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'url' => $this->url,
            'image' => $this->image,
            'date' => $this->date?->format('Y-m-d H:i:s'),
            'size' => (float) $this->size,
            'description' => $this->description,
            'download' => $this->download,
            'source' => $this->source,
            'actors' => $this->actors->pluck('name')->values()->toArray(),
            'tags' => $this->tags->pluck('name')->values()->toArray(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function searchableAs(): string
    {
        return 'jav';
    }
}
