<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users\Api;

use App\Models\User;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Rating;
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

        $myRating = Rating::query()->where('user_id', $user->id)->where('jav_id', $jav->id)->firstOrFail();
        $otherRating = Rating::factory()->create([
            'user_id' => $other->id,
            'jav_id' => $jav->id,
            'rating' => 3,
        ]);

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
}
