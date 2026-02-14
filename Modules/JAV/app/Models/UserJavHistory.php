<?php

namespace Modules\JAV\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserJavHistory extends Model
{
    use HasFactory;

    protected $table = 'user_jav_history';

    protected static function newFactory()
    {
        return \Database\Factories\UserJavHistoryFactory::new();
    }

    protected $fillable = [
        'user_id',
        'jav_id',
        'action',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jav(): BelongsTo
    {
        return $this->belongsTo(Jav::class);
    }
}
