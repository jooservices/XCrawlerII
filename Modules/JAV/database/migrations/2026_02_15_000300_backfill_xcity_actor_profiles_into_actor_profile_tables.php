<?php

use Illuminate\Database\Migrations\Migration;
use Modules\JAV\Models\Actor;
use Modules\JAV\Services\ActorProfileUpsertService;

return new class extends Migration
{
    public function up(): void
    {
        $upsertService = app(ActorProfileUpsertService::class);

        Actor::query()
            ->where(function ($query): void {
                $query->whereNotNull('xcity_id')
                    ->orWhereNotNull('xcity_profile')
                    ->orWhereNotNull('xcity_synced_at');
            })
            ->orderBy('id')
            ->chunkById(200, function ($actors) use ($upsertService): void {
                foreach ($actors as $actor) {
                    $mapped = [];
                    $raw = [];

                    if (is_array($actor->xcity_profile)) {
                        $mapped = is_array($actor->xcity_profile['mapped'] ?? null) ? $actor->xcity_profile['mapped'] : [];
                        $raw = is_array($actor->xcity_profile['raw'] ?? null) ? $actor->xcity_profile['raw'] : [];
                        if ($mapped === [] && $raw === []) {
                            $mapped = $actor->xcity_profile;
                        }
                    }

                    $attributes = [
                        'birth_date' => $actor->xcity_birth_date?->format('Y-m-d') ?? ($mapped['birth_date'] ?? null),
                        'blood_type' => $actor->xcity_blood_type ?? ($mapped['blood_type'] ?? null),
                        'city_of_birth' => $actor->xcity_city_of_birth ?? ($mapped['city_of_birth'] ?? null),
                        'height' => $actor->xcity_height ?? ($mapped['height'] ?? null),
                        'size' => $actor->xcity_size ?? ($mapped['size'] ?? null),
                        'hobby' => $actor->xcity_hobby ?? ($mapped['hobby'] ?? null),
                        'special_skill' => $actor->xcity_special_skill ?? ($mapped['special_skill'] ?? null),
                        'other' => $actor->xcity_other ?? ($mapped['other'] ?? null),
                    ];

                    foreach ($raw as $label => $value) {
                        if (!is_string($label) || !is_string($value)) {
                            continue;
                        }

                        $normalizedLabel = trim($label);
                        $normalizedValue = trim($value);
                        if ($normalizedLabel === '' || $normalizedValue === '') {
                            continue;
                        }

                        $key = 'raw.' . (preg_replace('/[^a-z0-9_]+/', '_', strtolower($normalizedLabel)) ?? '');
                        $key = rtrim($key, '_');
                        if ($key === 'raw.') {
                            continue;
                        }

                        $attributes[$key] = [
                            'value' => $normalizedValue,
                            'label' => $normalizedLabel,
                            'raw_value' => $normalizedValue,
                        ];
                    }

                    $upsertService->syncSource(
                        actor: $actor,
                        source: 'xcity',
                        sourceData: [
                            'source_actor_id' => $actor->xcity_id,
                            'source_url' => $actor->xcity_url,
                            'source_cover' => $actor->xcity_cover,
                            'payload' => is_array($actor->xcity_profile) ? $actor->xcity_profile : null,
                            'synced_at' => $actor->xcity_synced_at ?? now(),
                            'fetched_at' => $actor->xcity_synced_at ?? now(),
                        ],
                        attributes: $attributes,
                        isPrimary: true
                    );
                }
            });
    }

    public function down(): void
    {
        // Data backfill is not safely reversible.
    }
};
