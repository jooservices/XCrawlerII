<?php

declare(strict_types=1);

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\JAV\Database\Factories\TagFactory;

final class Tag extends Model
{
    use HasFactory;
    use HasUuids;

    public const string TABLE = 'tags';

    protected $table = self::TABLE;

    /** @var array<int, string> */
    protected $fillable = [
        'uuid',
        'name',
        'description',
    ];

    /** @var array<string, string> */
    protected $casts = [];

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsToMany<Movie, self> */
    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_tag');
    }

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }
}
