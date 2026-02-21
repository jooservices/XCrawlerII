<?php

namespace Modules\JAV\Tests\Feature\Requests;

use App\Models\User;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Rating;
use Modules\JAV\Models\Tag;
use Modules\JAV\Tests\TestCase;

class StoreRatingRequestValidationTest extends TestCase
{
    // ──────────────────────────────────────────
    // Happy path
    // ──────────────────────────────────────────

    public function test_valid_jav_id_with_rating_passes(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
                'rating' => 4,
            ])
            ->assertCreated();
    }

    public function test_valid_tag_id_with_rating_passes(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'tag_id' => $tag->id,
                'rating' => 3,
            ])
            ->assertCreated();
    }

    // ──────────────────────────────────────────
    // Unhappy path: mutual exclusivity
    // ──────────────────────────────────────────

    public function test_both_jav_id_and_tag_id_rejected(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();
        $tag = Tag::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
                'tag_id' => $tag->id,
                'rating' => 4,
            ])
            ->assertStatus(422);
    }

    public function test_neither_jav_id_nor_tag_id_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'rating' => 4,
            ])
            ->assertStatus(422);
    }

    // ──────────────────────────────────────────
    // Unhappy path: non-existent IDs
    // ──────────────────────────────────────────

    public function test_nonexistent_jav_id_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => 999999,
                'rating' => 4,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['jav_id']);
    }

    public function test_nonexistent_tag_id_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'tag_id' => 999999,
                'rating' => 4,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tag_id']);
    }

    // ──────────────────────────────────────────
    // Unhappy path: missing rating
    // ──────────────────────────────────────────

    public function test_missing_rating_rejected(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    // ──────────────────────────────────────────
    // Boundary: rating min/max
    // ──────────────────────────────────────────

    public function test_rating_min_boundary_accepted(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
                'rating' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('data.rating', 1);
    }

    public function test_rating_max_boundary_accepted(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
                'rating' => 5,
            ])
            ->assertCreated()
            ->assertJsonPath('data.rating', 5);
    }

    public function test_rating_zero_rejected(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
                'rating' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_rating_six_rejected(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
                'rating' => 6,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    // ──────────────────────────────────────────
    // Boundary: review length
    // ──────────────────────────────────────────

    public function test_review_exactly_1000_chars_accepted(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
                'rating' => 4,
                'review' => str_repeat('A', 1000),
            ])
            ->assertCreated();
    }

    public function test_review_1001_chars_rejected(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
                'rating' => 4,
                'review' => str_repeat('A', 1001),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['review']);
    }

    // ──────────────────────────────────────────
    // Weird cases
    // ──────────────────────────────────────────

    public function test_unicode_emoji_in_review_accepted(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
                'rating' => 5,
                'review' => '⭐🎬 Great movie! 日本語テスト',
            ])
            ->assertCreated();
    }

    // ──────────────────────────────────────────
    // Security cases
    // ──────────────────────────────────────────

    public function test_xss_in_review_is_stored_as_string(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
                'rating' => 3,
                'review' => '<script>alert("xss")</script>',
            ])
            ->assertCreated();

        // Verify stored literally, not sanitized at request level
        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'review' => '<script>alert("xss")</script>',
        ]);
    }

    public function test_mass_assignment_extra_user_id_ignored(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $jav = Jav::factory()->create();

        $this->actingAs($user)
            ->postJson(route('jav.api.ratings.store'), [
                'jav_id' => $jav->id,
                'rating' => 4,
                'user_id' => $otherUser->id, // should be ignored
            ])
            ->assertCreated();

        // The rating should belong to the authenticated user, not the other user
        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'jav_id' => $jav->id,
        ]);

        $this->assertDatabaseMissing('ratings', [
            'user_id' => $otherUser->id,
            'jav_id' => $jav->id,
        ]);
    }

    public function test_guest_user_rejected(): void
    {
        $jav = Jav::factory()->create();

        $this->postJson(route('jav.api.ratings.store'), [
            'jav_id' => $jav->id,
            'rating' => 4,
        ])->assertUnauthorized();
    }
}
