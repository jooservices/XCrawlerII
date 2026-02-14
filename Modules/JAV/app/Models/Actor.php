<?php

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Modules\JAV\Services\ActorProfileResolver;

class Actor extends Model
{
    use Searchable;

    protected $table = 'actors';

    protected $touches = ['javs'];

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
        $profileParts = array_values(array_filter(
            array_map(
                static fn (array $field): string => trim((string) ($field['value'] ?? '')),
                $resolvedFields
            ),
            static fn (string $value): bool => $value !== ''
        ));

        return [
            'id' => (string) $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'profile_primary_source' => $resolved['primary_source'],
            'profile_attributes' => collect($resolvedFields)->mapWithKeys(
                static fn (array $field, string $kind): array => [$kind => $field['value']]
            )->all(),
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
            'xcity_synced_at' => $this->xcity_synced_at?->format('Y-m-d H:i:s'),
            'bio' => implode(' ', $profileParts),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
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
        $showCover = config('jav.show_cover', false);
        $resolvedCover = app(ActorProfileResolver::class)->resolveCover($this);

        if (!$showCover || empty($resolvedCover)) {
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

        $age = now()->year - $birthDate->year;

        return $age >= 0 ? $age : null;
    }

    public function favorites(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }
}
