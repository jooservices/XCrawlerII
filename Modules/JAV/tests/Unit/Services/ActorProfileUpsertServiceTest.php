<?php

namespace Modules\JAV\Tests\Unit\Services;

use Modules\JAV\Models\Actor;
use Modules\JAV\Services\ActorProfileUpsertService;
use Modules\JAV\Tests\TestCase;

class ActorProfileUpsertServiceTest extends TestCase
{
    public function test_it_normalizes_and_skips_invalid_blood_type_values(): void
    {
        $actor = Actor::create(['name' => 'Blood Type Case']);
        $service = app(ActorProfileUpsertService::class);

        $service->syncSource(
            actor: $actor,
            source: 'xcity',
            sourceData: [
                'source_actor_id' => 'x-1',
                'synced_at' => now(),
            ],
            attributes: [
                'blood_type' => '- Type',
            ],
            isPrimary: true
        );

        $this->assertDatabaseMissing('actor_profile_attributes', [
            'actor_id' => $actor->id,
            'source' => 'xcity',
            'kind' => 'blood_type',
        ]);

        $service->syncSource(
            actor: $actor,
            source: 'xcity',
            sourceData: [
                'source_actor_id' => 'x-1',
                'synced_at' => now(),
            ],
            attributes: [
                'blood_type' => 'ab type',
            ],
            isPrimary: true
        );

        $this->assertDatabaseHas('actor_profile_attributes', [
            'actor_id' => $actor->id,
            'source' => 'xcity',
            'kind' => 'blood_type',
            'value_string' => 'AB',
        ]);
    }
}
