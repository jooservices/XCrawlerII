<?php

declare(strict_types=1);

namespace Modules\JAV\Models\MongoDb;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\JAV\Database\Factories\OnejavFactory;
use Modules\Core\Models\MongoDb;

final class Onejav extends MongoDb
{
    use HasFactory;

    public const string COLLECTION = 'onejav';

    protected $table = self::COLLECTION;

    /** @var array<int, string> */
    protected $fillable = [
        'code',
        'movie',
        'tags',
        'actors',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'movie' => 'array',
        'tags' => 'array',
        'actors' => 'array',
    ];

    protected static function newFactory(): OnejavFactory
    {
        return OnejavFactory::new();
    }
}
