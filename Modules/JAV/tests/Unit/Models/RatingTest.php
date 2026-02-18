<?php

namespace Modules\JAV\Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_rating_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();
        $rating = Interaction::factory()
            ->forJav($jav)
            ->rating(4)
            ->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $rating->user);
        $this->assertEquals($user->id, $rating->user->id);
    }

    public function test_rating_belongs_to_jav(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();
        $rating = Interaction::factory()
            ->forJav($jav)
            ->rating(4)
            ->create(['user_id' => $user->id]);

        $this->assertInstanceOf(Jav::class, $rating->item);
        $this->assertEquals($jav->id, $rating->item->id);
    }

    public function test_for_jav_scope_filters_by_movie(): void
    {
        $user = User::factory()->create();
        $jav1 = Jav::factory()->create();
        $jav2 = Jav::factory()->create();

        Interaction::factory()->forJav($jav1)->rating(3)->create(['user_id' => $user->id]);
        Interaction::factory()->forJav($jav2)->rating(4)->create(['user_id' => $user->id]);

        $jav1Ratings = Interaction::query()
            ->where('item_type', Interaction::morphTypeFor(Jav::class))
            ->where('action', Interaction::ACTION_RATING)
            ->where('item_id', $jav1->id)
            ->get();
        $jav2Ratings = Interaction::query()
            ->where('item_type', Interaction::morphTypeFor(Jav::class))
            ->where('action', Interaction::ACTION_RATING)
            ->where('item_id', $jav2->id)
            ->get();

        $this->assertEquals(1, $jav1Ratings->count());
        $this->assertEquals(1, $jav2Ratings->count());
        $this->assertEquals($jav1->id, $jav1Ratings->first()->item_id);
    }

    public function test_by_user_scope_filters_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $jav = Jav::factory()->create();

        Interaction::factory()->forJav($jav)->rating(3)->create(['user_id' => $user1->id]);
        Interaction::factory()->forJav($jav)->rating(4)->create(['user_id' => $user2->id]);

        $user1Ratings = Interaction::query()
            ->where('action', Interaction::ACTION_RATING)
            ->where('user_id', $user1->id)
            ->get();
        $user2Ratings = Interaction::query()
            ->where('action', Interaction::ACTION_RATING)
            ->where('user_id', $user2->id)
            ->get();

        $this->assertEquals(1, $user1Ratings->count());
        $this->assertEquals(1, $user2Ratings->count());
        $this->assertEquals($user1->id, $user1Ratings->first()->user_id);
    }

    public function test_with_stars_scope_filters_by_rating(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        Interaction::factory()->forJav($jav)->rating(5)->create(['user_id' => $user->id]);
        Interaction::factory()->forJav(Jav::factory()->create())->rating(3)->create(['user_id' => $user->id]);

        $fiveStarRatings = Interaction::query()
            ->where('action', Interaction::ACTION_RATING)
            ->where('value', 5)
            ->get();
        $threeStarRatings = Interaction::query()
            ->where('action', Interaction::ACTION_RATING)
            ->where('value', 3)
            ->get();

        $this->assertEquals(1, $fiveStarRatings->count());
        $this->assertEquals(1, $threeStarRatings->count());
        $this->assertEquals(5, $fiveStarRatings->first()->value);
        $this->assertEquals(3, $threeStarRatings->first()->value);
    }
}
