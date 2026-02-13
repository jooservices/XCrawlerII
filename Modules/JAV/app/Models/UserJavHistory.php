<?php

namespace Modules\JAV\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserJavHistory extends Model
{
    protected $table = 'user_jav_history';

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
