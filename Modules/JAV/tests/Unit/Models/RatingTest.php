<?php

namespace Modules\JAV\Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Rating;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_rating_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();
        $rating = Rating::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
        ]);

        $this->assertInstanceOf(User::class, $rating->user);
        $this->assertEquals($user->id, $rating->user->id);
    }

    public function test_rating_belongs_to_jav(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();
        $rating = Rating::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
        ]);

        $this->assertInstanceOf(Jav::class, $rating->jav);
        $this->assertEquals($jav->id, $rating->jav->id);
    }

    public function test_for_jav_scope_filters_by_movie(): void
    {
        $user = User::factory()->create();
        $jav1 = Jav::factory()->create();
        $jav2 = Jav::factory()->create();

        Rating::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav1->id,
        ]);

        Rating::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav2->id,
        ]);

        $jav1Ratings = Rating::forJav($jav1->id)->get();
        $jav2Ratings = Rating::forJav($jav2->id)->get();

        $this->assertEquals(1, $jav1Ratings->count());
        $this->assertEquals(1, $jav2Ratings->count());
        $this->assertEquals($jav1->id, $jav1Ratings->first()->jav_id);
    }

    public function test_by_user_scope_filters_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $jav = Jav::factory()->create();

        Rating::factory()->create([
            'user_id' => $user1->id,
            'jav_id' => $jav->id,
        ]);

        Rating::factory()->create([
            'user_id' => $user2->id,
            'jav_id' => $jav->id,
        ]);

        $user1Ratings = Rating::byUser($user1->id)->get();
        $user2Ratings = Rating::byUser($user2->id)->get();

        $this->assertEquals(1, $user1Ratings->count());
        $this->assertEquals(1, $user2Ratings->count());
        $this->assertEquals($user1->id, $user1Ratings->first()->user_id);
    }

    public function test_with_stars_scope_filters_by_rating(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        Rating::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'rating' => 5,
        ]);

        Rating::factory()->create([
            'user_id' => $user->id,
            'jav_id' => Jav::factory()->create()->id,
            'rating' => 3,
        ]);

        $fiveStarRatings = Rating::withStars(5)->get();
        $threeStarRatings = Rating::withStars(3)->get();

        $this->assertEquals(1, $fiveStarRatings->count());
        $this->assertEquals(1, $threeStarRatings->count());
        $this->assertEquals(5, $fiveStarRatings->first()->rating);
        $this->assertEquals(3, $threeStarRatings->first()->rating);
    }
}
