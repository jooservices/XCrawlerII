<?php

namespace Modules\JAV\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Watchlist extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\WatchlistFactory::new();
    }

    protected $fillable = [
        'user_id',
        'jav_id',
        'status',
    ];

    /**
     * Get the user that owns the watchlist item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the JAV movie associated with the watchlist item.
     */
    public function jav(): BelongsTo
    {
        return $this->belongsTo(Jav::class);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
