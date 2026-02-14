<?php

namespace Modules\JAV\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Favorite;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Rating;
use Modules\JAV\Models\Tag;
use Modules\JAV\Models\UserJavHistory;
use Modules\JAV\Models\UserLikeNotification;
use Modules\JAV\Models\Watchlist;
use Tests\TestCase;

class JavTest extends TestCase
{
    use RefreshDatabase;

    public function test_jav_has_actors_relationship(): void
    {
        $jav = Jav::factory()->create();
        $actor = Actor::factory()->create();
        $jav->actors()->attach($actor->id);

        $this->assertTrue($jav->fresh()->actors->contains($actor));
    }

    public function test_jav_has_tags_relationship(): void
    {
        $jav = Jav::factory()->create();
        $tag = Tag::factory()->create();
        $jav->tags()->attach($tag->id);

        $this->assertTrue($jav->fresh()->tags->contains($tag));
    }

    public function test_jav_has_favorites_relationship(): void
    {
        $jav = Jav::factory()->create();
        $favorite = Favorite::factory()->create([
            'favoritable_type' => Jav::class,
            'favoritable_id' => $jav->id,
        ]);

        $this->assertTrue($jav->favorites->contains($favorite));
    }

    public function test_jav_has_ratings_relationship(): void
    {
        $jav = Jav::factory()->create();
        $rating = Rating::factory()->create(['jav_id' => $jav->id]);

        $this->assertTrue($jav->ratings->contains($rating));
    }

    public function test_jav_has_watchlists_relationship(): void
    {
        $jav = Jav::factory()->create();
        $watchlist = Watchlist::factory()->create(['jav_id' => $jav->id]);

        $this->assertTrue($jav->watchlists->contains($watchlist));
    }

    public function test_jav_has_user_histories_relationship(): void
    {
        $jav = Jav::factory()->create();
        $history = UserJavHistory::factory()->create(['jav_id' => $jav->id]);

        $this->assertTrue($jav->userHistories->contains($history));
    }

    public function test_jav_has_like_notifications_relationship(): void
    {
        $jav = Jav::factory()->create();
        $notification = UserLikeNotification::factory()->create(['jav_id' => $jav->id]);

        $this->assertTrue($jav->likeNotifications->contains($notification));
    }
}
