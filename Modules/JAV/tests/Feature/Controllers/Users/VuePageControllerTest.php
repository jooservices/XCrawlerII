<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users;

use App\Models\User;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;
use Modules\JAV\Tests\Feature\Controllers\Concerns\InteractsWithInertiaPage;
use Modules\JAV\Tests\TestCase;

class VuePageControllerTest extends TestCase
{
    use InteractsWithInertiaPage;

    public function test_authenticated_user_can_render_core_vue_pages_with_expected_components(): void
    {
        config(['scout.driver' => 'collection']);

        $user = User::factory()->create();
        $actor = Actor::factory()->create();
        $jav = Jav::factory()->create();
        $rating = Interaction::factory()
            ->forJav($jav)
            ->rating(4)
            ->create(['user_id' => $user->id]);

        $cases = [
            ['route' => route('jav.vue.dashboard'), 'component' => 'Dashboard/Index', 'props' => ['items', 'filters', 'preferences']],
            ['route' => route('jav.vue.actors'), 'component' => 'Actors/Index', 'props' => ['actors', 'query']],
            ['route' => route('jav.vue.actors.bio', $actor), 'component' => 'Actors/Bio', 'props' => ['actor', 'movies', 'bioProfile', 'actorInsights']],
            ['route' => route('jav.vue.tags'), 'component' => 'Tags/Index', 'props' => ['tags', 'query']],
            ['route' => route('jav.vue.history'), 'component' => 'User/History', 'props' => ['history']],
            ['route' => route('jav.vue.favorites'), 'component' => 'User/Favorites', 'props' => ['favorites']],
            ['route' => route('jav.vue.watchlist'), 'component' => 'User/Watchlist', 'props' => ['watchlist', 'status']],
            ['route' => route('jav.vue.recommendations'), 'component' => 'User/Recommendations', 'props' => ['recommendations']],
            ['route' => route('jav.vue.ratings'), 'component' => 'Ratings/Index', 'props' => ['ratings']],
            ['route' => route('jav.vue.ratings.show', $rating), 'component' => 'Ratings/Show', 'props' => ['rating']],
            ['route' => route('jav.notifications'), 'component' => 'User/Notifications', 'props' => ['notifications']],
            ['route' => route('jav.vue.preferences'), 'component' => 'User/Preferences', 'props' => ['preferences']],
            ['route' => route('jav.vue.movies.show', $jav), 'component' => 'Movies/Show', 'props' => ['jav', 'relatedByActors', 'relatedByTags', 'isLiked']],
            ['route' => route('jav.vue.javs.index'), 'component' => 'Javs/Index', 'props' => ['items']],
            ['route' => route('jav.vue.javs.create'), 'component' => 'Javs/Create', 'props' => []],
            ['route' => route('jav.vue.javs.show', $jav), 'component' => 'Javs/Show', 'props' => ['item']],
            ['route' => route('jav.vue.javs.edit', $jav), 'component' => 'Javs/Edit', 'props' => ['item']],
        ];

        foreach ($cases as $case) {
            $response = $this->actingAs($user)->get($case['route']);
            $this->assertInertiaPage($response, $case['component'], $case['props']);
        }
    }

    public function test_guest_cannot_access_authenticated_vue_pages(): void
    {
        $response = $this->get(route('jav.vue.dashboard'));

        $response->assertStatus(302);
    }
}
