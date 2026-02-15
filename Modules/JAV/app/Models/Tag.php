<?php

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;

class Tag extends Model
{
    use HasFactory, Searchable;

    protected $table = 'tags';

    protected static function newFactory()
    {
        return \Database\Factories\TagFactory::new();
    }

    public function searchable()
    {
        \Modules\JAV\Events\ContentSyncing::dispatch($this);
        parent::searchable();
        \Modules\JAV\Events\ContentSynced::dispatch($this);
    }

    protected $fillable = ['name'];

    protected $touches = ['javs'];

    public function javs(): BelongsToMany
    {
        return $this->belongsToMany(Jav::class, 'jav_tag');
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function searchableAs(): string
    {
        return 'tags';
    }

    public function favorites(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }
}
