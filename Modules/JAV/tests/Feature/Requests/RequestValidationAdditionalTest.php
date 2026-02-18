<?php

namespace Modules\JAV\Tests\Feature\Requests;

use App\Models\User;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Watchlist;
use Modules\JAV\Tests\TestCase;

class RequestValidationAdditionalTest extends TestCase
{
    public function test_ratings_index_rejects_invalid_sort_and_per_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('jav.vue.ratings', [
                'sort' => 'bad_sort',
                'per_page' => 1000,
            ]))
            ->assertStatus(302)
            ->assertSessionHasErrors(['sort', 'per_page']);
    }

    public function test_watchlist_index_rejects_invalid_status_filter(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('jav.vue.watchlist', [
                'status' => 'invalid_status',
            ]))
            ->assertStatus(302)
            ->assertSessionHasErrors(['status']);
    }

    public function test_ratings_update_rejects_review_longer_than_max_length(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();
        $rating = Interaction::factory()
            ->forJav($jav)
            ->rating(3)
            ->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->putJson(route('ratings.update', $rating), [
                'rating' => 4,
                'review' => str_repeat('x', 1001),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['review']);
    }

    public function test_watchlist_update_rejects_invalid_status_on_web_route(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();
        $watchlist = Watchlist::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'status' => 'to_watch',
        ]);

        $this->actingAs($user)
            ->put(route('watchlist.update', $watchlist), [
                'status' => 'invalid_status',
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors(['status']);
    }
}
