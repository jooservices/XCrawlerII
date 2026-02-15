<?php

namespace Modules\JAV\Tests\Unit\Models;

use App\Models\User;
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

    public function test_cover_respects_show_cover_config_when_no_user_preference_exists(): void
    {
        config(['jav.show_cover' => false]);
        $actor = Actor::factory()->create(['xcity_cover' => 'https://example.com/actor-cover.jpg']);

        $this->assertSame('https://placehold.co/300x400?text=Cover+Hidden', $actor->cover);
    }

    public function test_cover_preference_overrides_show_cover_config(): void
    {
        config(['jav.show_cover' => false]);
        $user = User::factory()->create([
            'preferences' => [
                'show_cover' => true,
            ],
        ]);
        $this->actingAs($user);

        $actor = Actor::factory()->create(['xcity_cover' => 'https://example.com/actor-cover.jpg']);

        $this->assertSame('https://example.com/actor-cover.jpg', $actor->cover);
    }
}
