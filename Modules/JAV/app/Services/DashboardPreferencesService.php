<?php

namespace Modules\JAV\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class DashboardPreferencesService
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(?Authenticatable $user = null): array
    {
        $defaults = [
            'show_cover' => (bool) config('jav.show_cover', false),
            'compact_mode' => false,
            'text_preference' => 'detailed',
            'saved_presets' => [],
        ];

        $currentUser = $user ?? auth()->user();
        if ($currentUser === null) {
            return $defaults;
        }

        $saved = $currentUser->preferences;
        if (! is_array($saved)) {
            return $defaults;
        }

        foreach ($defaults as $key => $defaultValue) {
            if (array_key_exists($key, $saved)) {
                $defaults[$key] = $saved[$key];
            }
        }

        return $defaults;
    }

    /**
     * @return array<int, string>
     */
    public function normalizeTagFilters(Request $request): array
    {
        $tagValues = $request->input('tags', []);
        if (! is_array($tagValues)) {
            $tagValues = [];
        }

        $allTags = array_merge(
            $tagValues,
            $this->explodeCsv($request->input('tag', ''))
        );

        return $this->normalizeTagValues($allTags);
    }

    /**
     * @return array<int, string>
     */
    public function normalizeTagValues(mixed $values): array
    {
        if (! is_array($values)) {
            return $this->explodeCsv((string) $values);
        }

        return collect($values)
            ->map(static fn (mixed $value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function explodeCsv(mixed $value): array
    {
        $value = (string) ($value ?? '');

        if (trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(static fn (string $part): string => trim($part))
            ->filter(static fn (string $part): bool => $part !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function availableBioKeys(): array
    {
        return [
            'birth_date' => 'Birth Date',
            'blood_type' => 'Blood Type',
            'city_of_birth' => 'City Of Birth',
            'height' => 'Height',
            'size' => 'Size',
            'hobby' => 'Hobby',
            'special_skill' => 'Special Skill',
            'other' => 'Other',
        ];
    }

    /**
     * @return array<int, array{key: ?string, value: ?string}>
     */
    public function normalizeBioFilters(mixed $bioFilters, mixed $singleBioKey = null, mixed $singleBioValue = null): array
    {
        $filters = is_array($bioFilters) ? $bioFilters : [];

        $normalized = collect($filters)
            ->map(function (mixed $row): array {
                if (! is_array($row)) {
                    return ['key' => null, 'value' => null];
                }

                $key = trim((string) ($row['key'] ?? ''));
                $value = trim((string) ($row['value'] ?? ''));

                return [
                    'key' => $key !== '' ? strtolower(str_replace(' ', '_', $key)) : null,
                    'value' => $value !== '' ? $value : null,
                ];
            })
            ->filter(static fn (array $row): bool => $row['key'] !== null || $row['value'] !== null)
            ->values();

        if ($normalized->isEmpty()) {
            $key = trim((string) ($singleBioKey ?? ''));
            $value = trim((string) ($singleBioValue ?? ''));

            if ($key !== '' || $value !== '') {
                $normalized->push([
                    'key' => $key !== '' ? strtolower(str_replace(' ', '_', $key)) : null,
                    'value' => $value !== '' ? $value : null,
                ]);
            }
        }

        return $normalized->all();
    }
}
