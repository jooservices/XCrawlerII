<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Favorite;
use Modules\JAV\Models\Jav;
use Modules\JAV\Tests\TestCase;

class DashboardControllerBehaviorTest extends TestCase
{
    public function test_guest_cannot_access_dashboard_page(): void
    {
        $this->get(route('jav.vue.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_ignores_out_of_range_saved_preset_and_keeps_query_from_request(): void
    {
        config(['scout.driver' => 'collection']);

        $matching = Jav::factory()->create(['title' => 'Alpha Match']);
        Jav::factory()->create(['title' => 'Beta']);

        $user = User::factory()->create([
            'preferences' => [
                'saved_presets' => [
                    ['name' => 'Preset1', 'query' => 'Nope'],
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('jav.vue.dashboard', [
                'q' => 'Alpha',
                'saved_preset' => 999,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Dashboard/Index', false)
                ->where('query', 'Alpha')
                ->where('savedPresetIndex', 999)
                ->where('items.data.0.uuid', $matching->uuid)
                ->has('items.data', 1)
            );
    }

    public function test_actors_page_marks_is_liked_for_current_user(): void
    {
        config(['scout.driver' => 'collection']);

        $user = User::factory()->create();
        $likedActor = Actor::factory()->create(['name' => 'Alpha Actor']);
        $otherActor = Actor::factory()->create(['name' => 'Beta Actor']);

        Favorite::query()->create([
            'user_id' => $user->id,
            'favoritable_type' => Actor::class,
            'favoritable_id' => $likedActor->id,
        ]);

        $this->actingAs($user)
            ->get(route('jav.vue.actors', [
                'sort' => 'name',
                'direction' => 'asc',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Actors/Index', false)
                ->where('actors.data.0.name', 'Alpha Actor')
                ->where('actors.data.1.name', 'Beta Actor')
                ->has('actors.data', 2)
            );

        $this->assertNotSame($likedActor->id, $otherActor->id);
    }

    public function test_dashboard_uses_env_show_cover_when_user_preference_is_missing(): void
    {
        config([
            'scout.driver' => 'collection',
            'jav.show_cover' => false,
        ]);

        $user = User::factory()->create([
            'preferences' => [
                'compact_mode' => false,
                'text_preference' => 'detailed',
                'saved_presets' => [],
            ],
        ]);
        $jav = Jav::factory()->create([
            'title' => 'Cover Default Hidden',
            'image' => 'https://example.com/jav-cover.jpg',
        ]);

        $this->actingAs($user)
            ->get(route('jav.vue.dashboard', ['q' => 'Cover Default Hidden']))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Dashboard/Index', false)
                ->where('items.data.0.uuid', $jav->uuid)
                ->where('items.data.0.cover', 'https://placehold.co/300x400?text=Cover+Hidden')
                ->has('items.data', 1)
            );
    }

    public function test_dashboard_user_show_cover_preference_overrides_env_show_cover(): void
    {
        config([
            'scout.driver' => 'collection',
            'jav.show_cover' => false,
        ]);

        $user = User::factory()->create([
            'preferences' => [
                'show_cover' => true,
                'compact_mode' => false,
                'text_preference' => 'detailed',
                'saved_presets' => [],
            ],
        ]);
        $jav = Jav::factory()->create([
            'title' => 'Cover Override Visible',
            'image' => 'https://example.com/jav-cover-visible.jpg',
        ]);

        $this->actingAs($user)
            ->get(route('jav.vue.dashboard', ['q' => 'Cover Override Visible']))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Dashboard/Index', false)
                ->where('items.data.0.uuid', $jav->uuid)
                ->where('items.data.0.cover', 'https://example.com/jav-cover-visible.jpg')
                ->has('items.data', 1)
            );
    }

    public function test_actor_bio_uses_env_show_cover_when_user_preference_is_missing(): void
    {
        config(['jav.show_cover' => false]);

        $user = User::factory()->create([
            'preferences' => [
                'compact_mode' => false,
                'text_preference' => 'detailed',
                'saved_presets' => [],
            ],
        ]);
        $actor = Actor::factory()->create([
            'name' => 'Hidden Cover Actor',
            'xcity_cover' => 'https://example.com/actor-hidden.jpg',
        ]);

        $this->actingAs($user)
            ->get(route('jav.vue.actors.bio', $actor))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Actors/Bio', false)
                ->where('actor.uuid', $actor->uuid)
                ->where('actor.cover', 'https://placehold.co/300x400?text=Cover+Hidden')
            );
    }

    public function test_actor_bio_user_show_cover_preference_overrides_env_show_cover(): void
    {
        config(['jav.show_cover' => false]);

        $user = User::factory()->create([
            'preferences' => [
                'show_cover' => true,
                'compact_mode' => false,
                'text_preference' => 'detailed',
                'saved_presets' => [],
            ],
        ]);
        $actor = Actor::factory()->create([
            'name' => 'Visible Cover Actor',
            'xcity_cover' => 'https://example.com/actor-visible.jpg',
        ]);

        $this->actingAs($user)
            ->get(route('jav.vue.actors.bio', $actor))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Actors/Bio', false)
                ->where('actor.uuid', $actor->uuid)
                ->where('actor.cover', 'https://example.com/actor-visible.jpg')
            );
    }

    public function test_dashboard_rejects_invalid_sort_field_payload(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('jav.vue.dashboard', [
                'sort' => 'downloads;drop table javs',
            ]))
            ->assertStatus(302)
            ->assertSessionHasErrors(['sort']);
    }

    public function test_dashboard_rejects_overlong_query_payload(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('jav.vue.dashboard', [
                'q' => str_repeat('x', 256),
            ]))
            ->assertStatus(302)
            ->assertSessionHasErrors(['q']);
    }
}
