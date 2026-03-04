<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

final class Reaction extends Model
{
    public const string TABLE = 'reactions';

    protected $table = self::TABLE;

    /** @var array<int, string> */
    protected $fillable = [
        'reactable_type',
        'reactable_id',
        'reaction',
        'count',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'count' => 'integer',
    ];
}
