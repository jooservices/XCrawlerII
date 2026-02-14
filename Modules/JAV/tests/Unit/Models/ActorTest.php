<?php

namespace Modules\JAV\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\ActorProfileAttribute;
use Modules\JAV\Models\ActorProfileSource;
use Modules\JAV\Models\Favorite;
use Modules\JAV\Models\Jav;
use Tests\TestCase;

class ActorTest extends TestCase
{
    use RefreshDatabase;

    public function test_actor_has_javs_relationship(): void
    {
        $actor = Actor::factory()->create();
        $jav = Jav::factory()->create();
        $actor->javs()->attach($jav->id);

        $this->assertTrue($actor->fresh()->javs->contains($jav));
    }

    public function test_actor_has_profile_sources_relationship(): void
    {
        $actor = Actor::factory()->create();
        $source = ActorProfileSource::factory()->create(['actor_id' => $actor->id]);

        $this->assertTrue($actor->profileSources->contains($source));
    }

    public function test_actor_has_profile_attributes_relationship(): void
    {
        $actor = Actor::factory()->create();
        $attribute = ActorProfileAttribute::factory()->create(['actor_id' => $actor->id]);

        $this->assertTrue($actor->profileAttributes->contains($attribute));
    }

    public function test_actor_has_favorites_relationship(): void
    {
        $actor = Actor::factory()->create();
        $favorite = Favorite::factory()->create([
            'favoritable_type' => Actor::class,
            'favoritable_id' => $actor->id,
        ]);

        $this->assertTrue($actor->favorites->contains($favorite));
    }
}
