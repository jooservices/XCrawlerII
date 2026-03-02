<?php

declare(strict_types=1);

namespace Modules\JAV\Models\MongoDb;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\JAV\Database\Factories\OneFourOneJavFactory;
use Modules\Core\Models\MongoDb;

final class OneFourOneJav extends MongoDb
{
    use HasFactory;

    public const string COLLECTION = '141jav';

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

    protected static function newFactory(): OneFourOneJavFactory
    {
        return OneFourOneJavFactory::new();
    }
}
