<?php

declare(strict_types=1);

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\JAV\Database\Factories\ActorFactory;

final class Actor extends Model
{
    use HasFactory;
    use HasUuids;

    public const string TABLE = 'actors';

    protected $table = self::TABLE;

    /** @var array<int, string> */
    protected $fillable = [
        'uuid',
        'name',
        'avatar',
        'aliases',
        'birth_date',
        'birthplace',
        'blood_type',
        'height',
        'weight',
        'bust',
        'waist',
        'hip',
        'cup_size',
        'hobbies',
        'skills',
        'attributes',
        'crawled_at',
        'seen_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'aliases' => 'array',
        'birth_date' => 'date',
        'hobbies' => 'array',
        'skills' => 'array',
        'attributes' => 'array',
        'crawled_at' => 'datetime',
        'seen_at' => 'datetime',
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

    /** @return BelongsToMany<Movie, self> */
    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_actor');
    }

    protected static function newFactory(): ActorFactory
    {
        return ActorFactory::new();
    }
}
