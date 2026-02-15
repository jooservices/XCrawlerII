<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users;

use App\Models\User;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\UserJavHistory;
use Modules\JAV\Tests\TestCase;

class MovieShowControllerTest extends TestCase
{
    public function test_show_movie_page_increments_views_and_tracks_history_for_authenticated_user(): void
    {
        config(['scout.driver' => 'collection']);

        $user = User::factory()->create();
        $jav = Jav::factory()->create(['views' => 3]);

        $this->actingAs($user)
            ->get(route('jav.vue.movies.show', $jav))
            ->assertOk();

        $this->assertSame(4, $jav->fresh()->views);

        $history = UserJavHistory::query()
            ->where('user_id', $user->id)
            ->where('jav_id', $jav->id)
            ->where('action', 'view')
            ->first();

        $this->assertNotNull($history);
    }

    public function test_guest_cannot_access_movie_page(): void
    {
        $jav = Jav::factory()->create();

        $this->get(route('jav.vue.movies.show', $jav))
            ->assertRedirect(route('login'));
    }

    public function test_show_movie_page_returns_not_found_for_unknown_uuid(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('jav.vue.movies.show', ['jav' => '00000000-0000-0000-0000-000000000000']))
            ->assertNotFound();
    }
}
