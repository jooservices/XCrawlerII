<?php

namespace Modules\JAV\Services;

use Illuminate\Support\Carbon;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\ActorProfileAttribute;
use Modules\JAV\Models\ActorProfileSource;

class ActorProfileUpsertService
{
    /**
     * @param  array{
     *   source_actor_id?: ?string,
     *   source_url?: ?string,
     *   source_cover?: ?string,
     *   payload?: ?array,
     *   fetched_at?: mixed,
     *   synced_at?: mixed
     * }  $sourceData
     * @param  array<string, string|int|float|array{
     *   value?: mixed,
     *   label?: ?string,
     *   raw_value?: ?string
     * }>  $attributes
     */
    public function syncSource(
        Actor $actor,
        string $source,
        array $sourceData,
        array $attributes,
        bool $isPrimary = false
    ): void {
        $source = strtolower(trim($source));
        if ($source === '') {
            return;
        }

        ActorProfileSource::query()->updateOrCreate(
            [
                'actor_id' => $actor->id,
                'source' => $source,
            ],
            [
                'source_actor_id' => $this->stringOrNull($sourceData['source_actor_id'] ?? null),
                'source_url' => $this->stringOrNull($sourceData['source_url'] ?? null),
                'source_cover' => $this->stringOrNull($sourceData['source_cover'] ?? null),
                'payload' => is_array($sourceData['payload'] ?? null) ? $sourceData['payload'] : null,
                'is_primary' => $isPrimary,
                'fetched_at' => $this->timestampOrNull($sourceData['fetched_at'] ?? null),
                'synced_at' => $this->timestampOrNull($sourceData['synced_at'] ?? null),
            ]
        );

        $kinds = [];
        foreach ($attributes as $kind => $attribute) {
            $normalizedKind = $this->normalizeKind((string) $kind);
            if ($normalizedKind === '') {
                continue;
            }

            $value = $attribute;
            $label = null;
            $rawValue = null;

            if (is_array($attribute)) {
                $value = $attribute['value'] ?? null;
                $label = $this->stringOrNull($attribute['label'] ?? null);
                $rawValue = $this->stringOrNull($attribute['raw_value'] ?? null);
            }

            [$valueString, $valueNumber, $valueDate] = $this->splitTypedValue($normalizedKind, $value);
            if ($valueString === null && $valueNumber === null && $valueDate === null) {
                continue;
            }
            $kinds[] = $normalizedKind;

            ActorProfileAttribute::query()->updateOrCreate(
                [
                    'actor_id' => $actor->id,
                    'source' => $source,
                    'kind' => $normalizedKind,
                ],
                [
                    'value_string' => $valueString,
                    'value_number' => $valueNumber,
                    'value_date' => $valueDate,
                    'value_label' => $label,
                    'raw_value' => $rawValue,
                    'is_primary' => $isPrimary,
                    'synced_at' => $this->timestampOrNull($sourceData['synced_at'] ?? null) ?? now(),
                ]
            );
        }

        if ($kinds !== []) {
            ActorProfileAttribute::query()
                ->where('actor_id', $actor->id)
                ->where('source', $source)
                ->whereNotIn('kind', $kinds)
                ->delete();
        }
    }

    private function normalizeKind(string $kind): string
    {
        $kind = strtolower(trim($kind));
        if ($kind === '') {
            return '';
        }

        if (str_starts_with($kind, 'raw.')) {
            $suffix = preg_replace('/[^a-z0-9_]+/', '_', substr($kind, 4)) ?? '';

            return 'raw.' . trim($suffix, '_');
        }

        $normalized = preg_replace('/[^a-z0-9_]+/', '_', $kind) ?? '';

        return trim($normalized, '_');
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function timestampOrNull(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        try {
            return Carbon::parse($normalized);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{0: ?string, 1: ?float, 2: ?string}
     */
    private function splitTypedValue(string $kind, mixed $value): array
    {
        $valueString = $this->stringOrNull($value);
        $valueNumber = null;
        $valueDate = null;

        if ($kind === 'blood_type') {
            $valueString = $this->normalizeBloodType($valueString);
        }

        if ($kind === 'birth_date') {
            $valueDate = $this->normalizeDate($valueString);
            if ($valueDate !== null) {
                $valueString = $valueDate;
            }
        }

        if (is_scalar($value) && is_numeric((string) $value)) {
            $valueNumber = (float) $value;
        }

        return [$valueString, $valueNumber, $valueDate];
    }

    private function normalizeDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $formats = ['Y-m-d', 'Y M d', 'Y F d', 'Y/m/d'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeBloodType(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) preg_replace('/\s+/u', ' ', $value));
        if ($normalized === '' || $normalized === '- Type' || $normalized === '-') {
            return null;
        }

        if (preg_match('/^(A|B|O|AB)\s*Type$/i', $normalized, $matches)) {
            return strtoupper($matches[1]);
        }

        if (preg_match('/^(A|B|O|AB)$/i', $normalized, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }
}
