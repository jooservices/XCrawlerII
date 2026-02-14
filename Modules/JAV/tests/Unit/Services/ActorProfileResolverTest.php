<?php

namespace Modules\JAV\Tests\Unit\Services;

use Illuminate\Support\Carbon;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\ActorProfileAttribute;
use Modules\JAV\Models\ActorProfileSource;
use Modules\JAV\Services\ActorProfileResolver;
use Modules\JAV\Tests\TestCase;

class ActorProfileResolverTest extends TestCase
{
    public function test_primary_source_overrides_duplicate_field_values(): void
    {
        $actor = Actor::create(['name' => 'Mio Example']);

        ActorProfileSource::create([
            'actor_id' => $actor->id,
            'source' => 'other',
            'is_primary' => false,
            'synced_at' => Carbon::parse('2026-02-10 10:00:00'),
        ]);
        ActorProfileSource::create([
            'actor_id' => $actor->id,
            'source' => 'xcity',
            'is_primary' => true,
            'synced_at' => Carbon::parse('2026-02-09 10:00:00'),
        ]);

        ActorProfileAttribute::create([
            'actor_id' => $actor->id,
            'source' => 'other',
            'kind' => 'height',
            'value_string' => '160cm',
            'is_primary' => false,
            'synced_at' => Carbon::parse('2026-02-10 10:00:00'),
        ]);
        ActorProfileAttribute::create([
            'actor_id' => $actor->id,
            'source' => 'xcity',
            'kind' => 'height',
            'value_string' => '155cm',
            'is_primary' => true,
            'synced_at' => Carbon::parse('2026-02-09 10:00:00'),
        ]);
        ActorProfileAttribute::create([
            'actor_id' => $actor->id,
            'source' => 'other',
            'kind' => 'hobby',
            'value_string' => 'Games',
            'is_primary' => false,
            'synced_at' => Carbon::parse('2026-02-10 10:00:00'),
        ]);

        $resolver = app(ActorProfileResolver::class);
        $resolved = $resolver->resolve($actor->fresh());

        $this->assertSame('xcity', $resolved['primary_source']);
        $this->assertSame('155cm', $resolved['fields']['height']['value']);
        $this->assertSame('xcity', $resolved['fields']['height']['source']);
        $this->assertSame('Games', $resolved['fields']['hobby']['value']);
        $this->assertSame('other', $resolved['fields']['hobby']['source']);
    }

    public function test_it_falls_back_to_legacy_xcity_columns_when_attributes_do_not_exist(): void
    {
        $actor = Actor::create([
            'name' => 'Airi Legacy',
            'xcity_birth_date' => '1990-01-01',
            'xcity_height' => '158cm',
        ]);

        $resolver = app(ActorProfileResolver::class);
        $display = $resolver->toDisplayMap($actor->fresh());

        $this->assertArrayHasKey('Birthdate', $display);
        $this->assertSame('158cm', $display['Height']);
    }
}
