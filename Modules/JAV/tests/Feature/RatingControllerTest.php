<?php

namespace Modules\JAV\Tests\Feature;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Rating;
use Tests\TestCase;

class RatingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_anyone_can_view_ratings(): void
    {
        $this->actingAs($this->user);
        $response = $this->get(route('jav.vue.ratings'));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('Ratings/Index', false)
                    ->has('ratings.data')
            );
    }

    public function test_ratings_can_be_filtered_by_jav_id(): void
    {
        $jav1 = Jav::factory()->create();
        $jav2 = Jav::factory()->create();

        Rating::factory()->create(['jav_id' => $jav1->id]);
        Rating::factory()->create(['jav_id' => $jav2->id]);

        $this->actingAs($this->user);
        $response = $this->get(route('jav.vue.ratings', ['jav_id' => $jav1->id]));
        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page): Assert => $page
                    ->component('Ratings/Index', false)
                    ->has('ratings.data', 1)
            );
    }

    public function test_authenticated_user_can_submit_rating(): void
    {
        $jav = Jav::factory()->create();

        $response = $this->actingAs($this->user)->postJson(route('ratings.store'), [
            'jav_id' => $jav->id,
            'rating' => 5,
            'review' => 'Great movie!',
        ]);

        $response->assertCreated();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('ratings', [
            'user_id' => $this->user->id,
            'jav_id' => $jav->id,
            'rating' => 5,
            'review' => 'Great movie!',
        ]);
    }

    public function test_guest_cannot_submit_rating(): void
    {
        $jav = Jav::factory()->create();

        $response = $this->postJson(route('ratings.store'), [
            'jav_id' => $jav->id,
            'rating' => 5,
        ]);

        $response->assertUnauthorized();
    }

    public function test_rating_requires_jav_id(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('ratings.store'), [
            'rating' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['jav_id']);
    }

    public function test_rating_requires_valid_jav_id(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('ratings.store'), [
            'jav_id' => 99999,
            'rating' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['jav_id']);
    }

    public function test_rating_must_be_between_1_and_5(): void
    {
        $jav = Jav::factory()->create();

        $response = $this->actingAs($this->user)->postJson(route('ratings.store'), [
            'jav_id' => $jav->id,
            'rating' => 6,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rating']);

        $response = $this->actingAs($this->user)->postJson(route('ratings.store'), [
            'jav_id' => $jav->id,
            'rating' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rating']);
    }

    public function test_user_cannot_rate_same_movie_twice(): void
    {
        $jav = Jav::factory()->create();

        Rating::factory()->create([
            'user_id' => $this->user->id,
            'jav_id' => $jav->id,
            'rating' => 4,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('ratings.store'), [
            'jav_id' => $jav->id,
            'rating' => 5,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('ratings', 1);
    }

    public function test_user_can_update_their_own_rating(): void
    {
        $jav = Jav::factory()->create();
        $rating = Rating::factory()->create([
            'user_id' => $this->user->id,
            'jav_id' => $jav->id,
            'rating' => 4,
            'review' => 'Good',
        ]);

        $response = $this->actingAs($this->user)->putJson(route('ratings.update', $rating), [
            'rating' => 5,
            'review' => 'Excellent!',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('ratings', [
            'id' => $rating->id,
            'rating' => 5,
            'review' => 'Excellent!',
        ]);
    }

    public function test_user_cannot_update_another_users_rating(): void
    {
        $otherUser = User::factory()->create();
        $jav = Jav::factory()->create();
        $rating = Rating::factory()->create([
            'user_id' => $otherUser->id,
            'jav_id' => $jav->id,
        ]);

        $response = $this->actingAs($this->user)->putJson(route('ratings.update', $rating), [
            'rating' => 5,
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_delete_their_own_rating(): void
    {
        $jav = Jav::factory()->create();
        $rating = Rating::factory()->create([
            'user_id' => $this->user->id,
            'jav_id' => $jav->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson(route('ratings.destroy', $rating));

        $response->assertOk();
        $this->assertDatabaseMissing('ratings', ['id' => $rating->id]);
    }

    public function test_user_cannot_delete_another_users_rating(): void
    {
        $otherUser = User::factory()->create();
        $jav = Jav::factory()->create();
        $rating = Rating::factory()->create([
            'user_id' => $otherUser->id,
            'jav_id' => $jav->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson(route('ratings.destroy', $rating));

        $response->assertForbidden();
        $this->assertDatabaseHas('ratings', ['id' => $rating->id]);
    }

    public function test_check_endpoint_returns_user_rating_if_exists(): void
    {
        $jav = Jav::factory()->create();
        $rating = Rating::factory()->create([
            'user_id' => $this->user->id,
            'jav_id' => $jav->id,
            'rating' => 4,
            'review' => 'Nice',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('ratings.check', $jav->id));

        $response->assertOk();
        $response->assertJson([
            'has_rated' => true,
            'rating' => 4,
            'review' => 'Nice',
            'id' => $rating->id,
        ]);
    }

    public function test_check_endpoint_returns_false_if_no_rating(): void
    {
        $jav = Jav::factory()->create();

        $response = $this->actingAs($this->user)->getJson(route('ratings.check', $jav->id));

        $response->assertOk();
        $response->assertJson([
            'has_rated' => false,
        ]);
    }

    public function test_ratings_can_be_sorted_by_recent(): void
    {
        $jav = Jav::factory()->create();
        $old = Rating::factory()->create([
            'jav_id' => $jav->id,
            'created_at' => now()->subDays(5),
        ]);
        $new = Rating::factory()->create([
            'jav_id' => $jav->id,
            'created_at' => now(),
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('jav.vue.ratings', ['jav_id' => $jav->id, 'sort' => 'recent']));
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page): Assert => $page
                ->component('Ratings/Index', false)
                ->has('ratings.data', 2)
        );
    }

    public function test_ratings_can_be_sorted_by_highest(): void
    {
        $jav = Jav::factory()->create();
        Rating::factory()->create(['jav_id' => $jav->id, 'rating' => 3]);
        $highest = Rating::factory()->create(['jav_id' => $jav->id, 'rating' => 5]);

        $this->actingAs($this->user);
        $response = $this->get(route('jav.vue.ratings', ['jav_id' => $jav->id, 'sort' => 'highest']));
        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page): Assert => $page
                ->component('Ratings/Index', false)
                ->has('ratings.data', 2)
        );
    }

    public function test_review_is_optional(): void
    {
        $jav = Jav::factory()->create();

        $response = $this->actingAs($this->user)->postJson(route('ratings.store'), [
            'jav_id' => $jav->id,
            'rating' => 5,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('ratings', [
            'user_id' => $this->user->id,
            'jav_id' => $jav->id,
            'rating' => 5,
            'review' => null,
        ]);
    }
}
