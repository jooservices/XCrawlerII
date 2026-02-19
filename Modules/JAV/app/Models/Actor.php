<?php

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Modules\JAV\Services\ActorProfileResolver;

class Actor extends Model
{
    use HasFactory, Searchable;

    private const SEARCH_DATETIME_FORMAT = 'Y-m-d H:i:s';

    protected $table = 'actors';

    protected $appends = ['age', 'cover', 'is_featured', 'featured_curation_uuid'];

    protected $touches = ['javs'];

    protected static function newFactory()
    {
        return \Database\Factories\ActorFactory::new();
    }

    public function searchable()
    {
        \Modules\JAV\Events\ContentSyncing::dispatch($this);
        parent::searchable();
        \Modules\JAV\Events\ContentSynced::dispatch($this);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'uuid',
        'name',
        'xcity_id',
        'xcity_url',
        'xcity_cover',
        'xcity_birth_date',
        'xcity_blood_type',
        'xcity_city_of_birth',
        'xcity_height',
        'xcity_size',
        'xcity_hobby',
        'xcity_special_skill',
        'xcity_other',
        'xcity_profile',
        'xcity_synced_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'xcity_birth_date' => 'date',
        'xcity_profile' => 'array',
        'xcity_synced_at' => 'datetime',
    ];

    public function javs(): BelongsToMany
    {
        return $this->belongsToMany(Jav::class, 'jav_actor');
    }

    public function profileSources(): HasMany
    {
        return $this->hasMany(ActorProfileSource::class, 'actor_id');
    }

    public function profileAttributes(): HasMany
    {
        return $this->hasMany(ActorProfileAttribute::class, 'actor_id');
    }

    /**
     * @return array{
     *   primary_source: ?string,
     *   fields: array<string, array{value: string, source: string, label: ?string}>
     * }
     */
    public function resolvedProfile(): array
    {
        return app(ActorProfileResolver::class)->resolve($this);
    }

    public function toSearchableArray(): array
    {
        $resolved = $this->resolvedProfile();
        $resolvedFields = $resolved['fields'];
        $profileAttributeMap = collect($resolvedFields)->mapWithKeys(
            static fn (array $field, string $kind): array => [$kind => $field['value']]
        );
        $profileParts = array_values(array_filter(
            array_map(
                static fn (array $field): string => trim((string) ($field['value'] ?? '')),
                $resolvedFields
            ),
            static fn (string $value): bool => $value !== ''
        ));
        $profilePairs = $profileAttributeMap
            ->map(function (mixed $value, string $key): string {
                $normalizedKey = strtolower(trim((string) $key));
                $normalizedValue = strtolower(trim((string) $value));

                return "{$normalizedKey}:{$normalizedValue}";
            })
            ->filter(static fn (string $value): bool => $value !== ':' && $value !== '')
            ->values()
            ->all();
        $movieTags = $this->javs()
            ->with('tags:id,name')
            ->get()
            ->flatMap(static fn ($jav) => $jav->tags->pluck('name'))
            ->filter(static fn ($name): bool => trim((string) $name) !== '')
            ->unique()
            ->values()
            ->all();
        $birthDate = app(ActorProfileResolver::class)->resolveBirthDate($this);

        return [
            'id' => (string) $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'name_keyword' => mb_strtolower((string) $this->name),
            'javs_count' => (int) $this->javs()->count(),
            'movie_tags' => $movieTags,
            'movie_tags_keyword' => array_map(
                static fn (string $tag): string => mb_strtolower(trim($tag)),
                $movieTags
            ),
            'profile_primary_source' => $resolved['primary_source'],
            'profile_attributes' => $profileAttributeMap->all(),
            'profile_attribute_keys' => $profileAttributeMap->keys()->values()->all(),
            'profile_attribute_pairs' => $profilePairs,
            'xcity_id' => $this->xcity_id,
            'xcity_url' => $this->xcity_url,
            'xcity_cover' => $this->xcity_cover,
            'xcity_birth_date' => $this->xcity_birth_date?->format('Y-m-d'),
            'xcity_blood_type' => $this->xcity_blood_type,
            'xcity_city_of_birth' => $this->xcity_city_of_birth,
            'xcity_height' => $this->xcity_height,
            'xcity_size' => $this->xcity_size,
            'xcity_hobby' => $this->xcity_hobby,
            'xcity_special_skill' => $this->xcity_special_skill,
            'xcity_other' => $this->xcity_other,
            'xcity_profile' => $this->xcity_profile,
            'xcity_synced_at' => $this->xcity_synced_at?->format(self::SEARCH_DATETIME_FORMAT),
            'bio' => implode(' ', $profileParts),
            'bio_lower' => mb_strtolower(implode(' ', $profileParts)),
            'age' => $this->age,
            'birth_date' => $birthDate?->format('Y-m-d'),
            'created_at' => $this->created_at?->format(self::SEARCH_DATETIME_FORMAT),
            'updated_at' => $this->updated_at?->format(self::SEARCH_DATETIME_FORMAT),
        ];
    }

    public function searchableAs(): string
    {
        return 'actors';
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getCoverAttribute(): string
    {
        $showCover = (bool) config('jav.show_cover', false);
        $userPreferences = Auth::user()?->preferences;
        if (is_array($userPreferences) && array_key_exists('show_cover', $userPreferences)) {
            $showCover = (bool) $userPreferences['show_cover'];
        }
        $resolvedCover = app(ActorProfileResolver::class)->resolveCover($this);

        if (! $showCover || empty($resolvedCover)) {
            return 'https://placehold.co/300x400?text=Cover+Hidden';
        }

        return $resolvedCover;
    }

    public function getAgeAttribute(): ?int
    {
        $birthDate = app(ActorProfileResolver::class)->resolveBirthDate($this);
        if ($birthDate === null) {
            return null;
        }

        $age = $birthDate->age;

        return $age >= 0 ? $age : null;
    }

    /**
     * Whether this actor is in the featured curation (set by CurationReadService).
     */
    public function getIsFeaturedAttribute(): bool
    {
        return (bool) ($this->attributes['is_featured'] ?? false);
    }

    /**
     * UUID of the featured curation entry if any (set by CurationReadService).
     */
    public function getFeaturedCurationUuidAttribute(): ?string
    {
        $value = $this->attributes['featured_curation_uuid'] ?? null;

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    public function favorites(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }
}
