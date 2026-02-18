<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users\Api;

use App\Models\User;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;
use Modules\JAV\Tests\TestCase;

class RatingControllerContractTest extends TestCase
{
    public function test_ratings_api_contract_auth_validation_and_ownership(): void
    {
        $jav = Jav::factory()->create();
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->postJson(route('jav.api.ratings.store'), [
            'jav_id' => $jav->id,
            'rating' => 5,
        ])->assertUnauthorized();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
                'rating' => 5,
                'review' => 'Nice',
            ])
            ->assertCreated()
            ->assertJsonStructure(['success', 'message', 'data'])
            ->assertJsonPath('success', true);

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
                'rating' => 4,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);

        $myRating = Interaction::query()
            ->where('user_id', $user->id)
            ->where('item_type', Interaction::morphTypeFor(Jav::class))
            ->where('item_id', $jav->id)
            ->where('action', Interaction::ACTION_RATING)
            ->firstOrFail();
        $otherRating = Interaction::factory()
            ->forJav($jav)
            ->rating(3)
            ->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->putJson(route('jav.api.ratings.update', $myRating), [
                'rating' => 2,
                'review' => 'Updated',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($user)
            ->putJson(route('jav.api.ratings.update', $otherRating), [
                'rating' => 2,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->deleteJson(route('jav.api.ratings.destroy', $otherRating))
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->actingAs($user)
            ->deleteJson(route('jav.api.ratings.destroy', $myRating))
            ->assertOk()
            ->assertJsonPath('success', true);

        auth()->logout();

        $this->getJson(route('jav.api.ratings.check', $jav->id))->assertUnauthorized();

        $this->actingAs($user)
            ->getJson(route('jav.api.ratings.check', $jav->id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('has_rated', false);
    }

    public function test_ratings_weird_case_accepts_minimum_rating_boundary(): void
    {
        $jav = Jav::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
                'rating' => 1,
                'review' => '',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rating', 1);
    }

    public function test_ratings_exploit_case_rejects_out_of_range_rating_payload(): void
    {
        $jav = Jav::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
                'rating' => 999,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }
}
