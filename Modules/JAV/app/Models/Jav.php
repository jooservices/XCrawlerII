<?php

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Jav extends Model
{
    use HasFactory, Searchable;

    protected $table = 'jav';

    /**
     * Get the factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\JavFactory::new();
    }

    public function searchable()
    {
        \Modules\JAV\Events\ContentSyncing::dispatch($this);
        parent::searchable();
        \Modules\JAV\Events\ContentSynced::dispatch($this);
    }

    /**
     * Boot the model and auto-generate UUID for new records.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key name for Laravel route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected $fillable = [
        'uuid',
        'item_id',
        'code',
        'title',
        'url',
        'image',
        'date',
        'size',
        'description',
        'download',
        'source',
        'views',
        'downloads',
    ];

    protected $casts = [
        'uuid' => 'string',
        'date' => 'datetime',
        'size' => 'float',
        'views' => 'integer',
        'downloads' => 'integer',
    ];

    public function actors(): BelongsToMany
    {
        return $this->belongsToMany(Actor::class, 'jav_actor');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'jav_tag');
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'uuid' => $this->uuid,
            'code' => $this->code,
            'title' => $this->title,
            'url' => $this->url,
            'image' => $this->image,
            'date' => $this->date?->format('Y-m-d H:i:s'),
            'size' => (float) $this->size,
            'description' => $this->description,
            'download' => $this->download,
            'source' => $this->source,
            'views' => (int) $this->views,
            'downloads' => (int) $this->downloads,
            'actors' => $this->actors->pluck('name')->values()->toArray(),
            'tags' => $this->tags->pluck('name')->values()->toArray(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function searchableAs(): string
    {
        return 'jav';
    }

    /**
     * Get the formatted code with hyphen between letters and numbers.
     * Example: MUDR360 -> MUDR-360
     */
    public function getFormattedCodeAttribute(): string
    {
        if (empty($this->code)) {
            return '';
        }

        // Insert hyphen between alphabetic and numeric characters
        return preg_replace('/([A-Za-z]+)(\d+)/', '$1-$2', $this->code);
    }

    /**
     * Get the cover image URL.
     * Returns placeholder if show_cover is disabled or image is empty.
     */
    public function getCoverAttribute(): string
    {
        $showCover = config('jav.show_cover', false);

        if (!$showCover || empty($this->image)) {
            return 'https://placehold.co/300x400?text=Cover+Hidden';
        }

        return $this->image;
    }
    public function favorites(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function ratings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function watchlists(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Watchlist::class);
    }

    public function userHistories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserJavHistory::class);
    }

    public function likeNotifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserLikeNotification::class, 'jav_id');
    }

    /**
     * Get the average rating for this movie.
     */
    public function getAverageRatingAttribute(): ?float
    {
        $average = $this->ratings()->avg('rating');
        return $average ? round($average, 1) : null;
    }

    /**
     * Get the total number of ratings for this movie.
     */
    public function getRatingsCountAttribute(): int
    {
        return $this->ratings()->count();
    }
}
