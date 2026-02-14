<?php

namespace Modules\JAV\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLikeNotification extends Model
{
    use HasFactory;

    protected $table = 'user_like_notifications';

    protected static function newFactory()
    {
        return \Database\Factories\UserLikeNotificationFactory::new();
    }

    protected $fillable = [
        'user_id',
        'jav_id',
        'dedupe_key',
        'title',
        'message',
        'payload',
        'read_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jav(): BelongsTo
    {
        return $this->belongsTo(Jav::class, 'jav_id');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function markAsRead(): void
    {
        if ($this->read_at !== null) {
            return;
        }

        $this->forceFill(['read_at' => now()])->save();
    }
}
