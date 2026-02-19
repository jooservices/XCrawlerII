<?php

namespace Modules\JAV\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'jav_id',
        'tag_id',
        'rating',
        'review',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Get the factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\RatingFactory::new();
    }

    /**
     * Get the user that created the rating.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the movie that was rated.
     */
    public function jav(): BelongsTo
    {
        return $this->belongsTo(Jav::class);
    }

    /**
     * Get the tag that was rated.
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    /**
     * Scope a query to only include ratings for a specific movie.
     */
    public function scopeForJav($query, int $javId)
    {
        return $query->where('jav_id', $javId);
    }

    /**
     * Scope a query to only include ratings for a specific tag.
     */
    public function scopeForTag($query, int $tagId)
    {
        return $query->where('tag_id', $tagId);
    }

    /**
     * Scope a query to only include ratings by a specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include ratings with a specific star value.
     */
    public function scopeWithStars($query, int $stars)
    {
        return $query->where('rating', $stars);
    }
}
