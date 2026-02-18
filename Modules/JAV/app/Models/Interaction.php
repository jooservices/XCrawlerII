<?php

namespace Modules\JAV\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Interaction extends Model
{
    use HasFactory;

    public const ACTION_FAVORITE = 'favorite';
    public const ACTION_RATING = 'rating';
    public const ACTION_VIEW = 'view';

    protected $table = 'user_interactions';

    protected $fillable = [
        'user_id',
        'item_id',
        'item_type',
        'action',
        'value',
        'meta',
    ];

    protected $casts = [
        'value' => 'integer',
        'meta' => 'array',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\InteractionFactory::new();
    }

    public static function morphTypeFor(string $modelClass): string
    {
        return app($modelClass)->getMorphClass();
    }

    protected $appends = [
        'favoritable_type',
        'favoritable_id',
        'favoritable',
        'jav_id',
        'jav',
        'rating',
        'review',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): MorphTo
    {
        return $this->morphTo('item', 'item_type', 'item_id');
    }

    public function getFavoritableTypeAttribute(): string
    {
        return (string) $this->item_type;
    }

    public function getFavoritableIdAttribute(): int
    {
        return (int) $this->item_id;
    }

    public function getFavoritableAttribute(): mixed
    {
        return $this->item;
    }

    public function getJavIdAttribute(): ?int
    {
        if ($this->item_type !== self::morphTypeFor(Jav::class)) {
            return null;
        }

        return (int) $this->item_id;
    }

    public function getJavAttribute(): mixed
    {
        if ($this->item_type !== self::morphTypeFor(Jav::class)) {
            return null;
        }

        return $this->item;
    }

    public function getRatingAttribute(): ?int
    {
        if ($this->action !== self::ACTION_RATING) {
            return null;
        }

        return $this->value;
    }

    public function getReviewAttribute(): ?string
    {
        if ($this->action !== self::ACTION_RATING) {
            return null;
        }

        $review = $this->meta['review'] ?? null;
        return is_string($review) ? $review : null;
    }
}
