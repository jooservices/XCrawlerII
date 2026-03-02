<?php

declare(strict_types=1);

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\JAV\Database\Factories\MovieFactory;

final class Movie extends Model
{
    use HasFactory;
    use HasUuids;

    public const string TABLE = 'movies';

    protected $table = self::TABLE;

    /** @var array<int, string> */
    protected $fillable = [
        'uuid',
        'code',
        'item_id',
        'title',
        'description',
        'category',
        'cover',
        'trailer',
        'gallery',
        'is_censored',
        'has_subtitles',
        'subtitles',
        'release_date',
        'duration_minutes',
        'crawled_at',
        'seen_at',
        'attributes',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'gallery' => 'array',
        'is_censored' => 'boolean',
        'has_subtitles' => 'boolean',
        'subtitles' => 'array',
        'release_date' => 'date',
        'crawled_at' => 'datetime',
        'seen_at' => 'datetime',
        'attributes' => 'array',
    ];

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsToMany<Actor, self> */
    public function actors(): BelongsToMany
    {
        return $this->belongsToMany(Actor::class, 'movie_actor');
    }

    /** @return BelongsToMany<Tag, self> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'movie_tag');
    }

    protected static function newFactory(): MovieFactory
    {
        return MovieFactory::new();
    }
}
