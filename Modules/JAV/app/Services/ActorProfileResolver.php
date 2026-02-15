<?php

namespace Modules\JAV\Services;

use Illuminate\Support\Carbon;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\ActorProfileAttribute;
use Modules\JAV\Models\ActorProfileSource;

class ActorProfileResolver
{
    /**
     * @return array{
     *   primary_source: ?string,
     *   fields: array<string, array{value: string, source: string, label: ?string}>
     * }
     */
    public function resolve(Actor $actor): array
    {
        $primarySource = $this->resolvePrimarySource($actor);
        $attributes = $this->attributesForActor($actor);

        if ($attributes->isEmpty()) {
            return [
                'primary_source' => $primarySource,
                'fields' => $this->legacyFields($actor),
            ];
        }

        $fields = [];
        foreach ($attributes as $attribute) {
            $kind = (string) $attribute->kind;
            $value = $this->extractValue($attribute);

            if ($kind === '' || $value === null) {
                continue;
            }

            $fields[$kind] = [
                'value' => $value,
                'source' => (string) $attribute->source,
                'label' => $attribute->value_label,
            ];
        }

        return [
            'primary_source' => $primarySource,
            'fields' => $fields,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function toDisplayMap(Actor $actor): array
    {
        $resolved = $this->resolve($actor);
        $fields = $resolved['fields'];

        $display = [];
        foreach ($this->displayLabels() as $kind => $label) {
            if (! isset($fields[$kind])) {
                continue;
            }

            $value = trim((string) $fields[$kind]['value']);
            if ($value === '') {
                continue;
            }

            if ($kind === 'birth_date') {
                $birthDate = $this->resolveBirthDate($actor);
                if ($birthDate !== null) {
                    $age = now()->year - $birthDate->year;
                    if ($age >= 0) {
                        $value .= " - {$age}";
                    }
                }
            }

            $display[$label] = $value;
        }

        foreach ($fields as $kind => $field) {
            if (! str_starts_with($kind, 'raw.')) {
                continue;
            }

            $label = trim((string) ($field['label'] ?? ''));
            $value = trim((string) ($field['value'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }

            $display[$label] = $value;
        }

        return $display;
    }

    public function resolveCover(Actor $actor): ?string
    {
        $sources = $this->sourcesForActor($actor);
        foreach ($sources as $source) {
            $cover = trim((string) ($source->source_cover ?? ''));
            if ($cover !== '') {
                return $cover;
            }
        }

        $legacyCover = trim((string) ($actor->xcity_cover ?? ''));

        return $legacyCover !== '' ? $legacyCover : null;
    }

    public function resolveBirthDate(Actor $actor): ?Carbon
    {
        $resolved = $this->resolve($actor);
        $birthDate = trim((string) ($resolved['fields']['birth_date']['value'] ?? ''));
        if ($birthDate !== '') {
            try {
                return Carbon::parse($birthDate);
            } catch (\Throwable) {
            }
        }

        return $actor->xcity_birth_date;
    }

    private function resolvePrimarySource(Actor $actor): ?string
    {
        $sources = $this->sourcesForActor($actor);
        $first = $sources->first();

        return $first?->source;
    }

    /**
     * @return \Illuminate\Support\Collection<int, ActorProfileSource>
     */
    private function sourcesForActor(Actor $actor): \Illuminate\Support\Collection
    {
        $sources = $actor->relationLoaded('profileSources')
            ? $actor->profileSources
            : $actor->profileSources()->get();

        return $sources
            ->sortByDesc(fn (ActorProfileSource $source): int => $this->sourceRank(
                (string) $source->source,
                (bool) $source->is_primary,
                $source->synced_at
            ))
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, ActorProfileAttribute>
     */
    private function attributesForActor(Actor $actor): \Illuminate\Support\Collection
    {
        $attributes = $actor->relationLoaded('profileAttributes')
            ? $actor->profileAttributes
            : $actor->profileAttributes()->get();

        return $attributes
            ->sortByDesc(fn (ActorProfileAttribute $attribute): int => $this->sourceRank(
                (string) $attribute->source,
                (bool) $attribute->is_primary,
                $attribute->synced_at
            ))
            ->groupBy('kind')
            ->map(function (\Illuminate\Support\Collection $items): ActorProfileAttribute {
                return $items->first();
            })
            ->values();
    }

    private function sourceRank(string $source, bool $isPrimary, ?Carbon $syncedAt): int
    {
        $priority = (int) config('jav.profile_source_priority.'.strtolower($source), 0);
        $primaryScore = $isPrimary ? 1_000_000_000 : 0;
        $timeScore = $syncedAt?->timestamp ?? 0;

        return $primaryScore + ($priority * 10_000) + $timeScore;
    }

    private function extractValue(ActorProfileAttribute $attribute): ?string
    {
        if ($attribute->value_date !== null) {
            return $attribute->value_date->format('Y-m-d');
        }

        if ($attribute->value_string !== null) {
            $value = trim($attribute->value_string);

            return $value === '' ? null : $value;
        }

        if ($attribute->value_number !== null) {
            return rtrim(rtrim((string) $attribute->value_number, '0'), '.');
        }

        return null;
    }

    /**
     * @return array<string, array{value: string, source: string, label: ?string}>
     */
    private function legacyFields(Actor $actor): array
    {
        $fields = [
            'birth_date' => $actor->xcity_birth_date?->format('Y-m-d'),
            'blood_type' => $actor->xcity_blood_type,
            'city_of_birth' => $actor->xcity_city_of_birth,
            'height' => $actor->xcity_height,
            'size' => $actor->xcity_size,
            'hobby' => $actor->xcity_hobby,
            'special_skill' => $actor->xcity_special_skill,
            'other' => $actor->xcity_other,
        ];

        return collect($fields)
            ->mapWithKeys(static function (mixed $value, string $kind): array {
                $normalized = trim((string) ($value ?? ''));
                if ($normalized === '') {
                    return [];
                }

                return [
                    $kind => [
                        'value' => $normalized,
                        'source' => 'xcity',
                        'label' => null,
                    ],
                ];
            })
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function displayLabels(): array
    {
        return [
            'birth_date' => 'Birthdate',
            'blood_type' => 'Blood Type',
            'city_of_birth' => 'City of Born',
            'height' => 'Height',
            'size' => 'Size',
            'hobby' => 'Hobby',
            'special_skill' => 'Special Skill',
            'other' => 'Other',
        ];
    }
}
