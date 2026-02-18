<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users\Api;

use App\Models\User;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Watchlist;
use Modules\JAV\Tests\TestCase;

class WatchlistRatingValidationEdgeTest extends TestCase
{
    public function test_watchlist_store_rejects_invalid_status_value(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.watchlist.store'), [
                'jav_id' => $jav->id,
                'status' => 'invalid_status',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_watchlist_update_rejects_invalid_status_value(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();
        $watchlist = Watchlist::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'status' => 'to_watch',
        ]);

        $this->actingAs($user)
            ->putJson(route('jav.api.watchlist.update', $watchlist), [
                'status' => 'invalid_status',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_rating_store_rejects_review_longer_than_max_length(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
                'rating' => 4,
                'review' => str_repeat('x', 1001),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['review']);
    }

    public function test_rating_update_requires_rating_field(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();
        $rating = Interaction::factory()
            ->forJav($jav)
            ->rating(3)
            ->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->putJson(route('jav.api.ratings.update', $rating), [
                'review' => 'updated only',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }
}
