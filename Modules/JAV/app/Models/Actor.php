<?php

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;

class Actor extends Model
{
    use Searchable;

    protected $table = 'actors';

    protected $fillable = ['name'];

    public function javs(): BelongsToMany
    {
        return $this->belongsToMany(Jav::class, 'jav_actor');
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
        return 'actors';
    }
}
