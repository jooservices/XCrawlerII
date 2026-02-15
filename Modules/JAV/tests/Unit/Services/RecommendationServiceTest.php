<?php

namespace Modules\JAV\Tests\Unit\Services;

use App\Models\User;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Favorite;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Models\UserJavHistory;
use Modules\JAV\Services\RecommendationService;
use Modules\JAV\Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    public function test_get_recommendations_returns_empty_when_user_has_no_likes(): void
    {
        $user = User::factory()->create();

        $service = app(RecommendationService::class);
        $recommendations = $service->getRecommendations($user, 10);

        $this->assertTrue($recommendations->isEmpty());
    }

    public function test_get_recommendations_excludes_liked_and_viewed_movies_and_orders_by_popularity(): void
    {
        $user = User::factory()->create();
        $likedActor = Actor::factory()->create(['name' => 'Actor One']);
        $likedTag = Tag::factory()->create(['name' => 'Tag One']);

        Favorite::query()->create([
            'user_id' => $user->id,
            'favoritable_id' => $likedActor->id,
            'favoritable_type' => Actor::class,
        ]);
        Favorite::query()->create([
            'user_id' => $user->id,
            'favoritable_id' => $likedTag->id,
            'favoritable_type' => Tag::class,
        ]);

        $likedMovie = Jav::factory()->create(['views' => 999, 'downloads' => 999]);
        $likedMovie->actors()->attach($likedActor->id);
        Favorite::query()->create([
            'user_id' => $user->id,
            'favoritable_id' => $likedMovie->id,
            'favoritable_type' => Jav::class,
        ]);

        $viewedMovie = Jav::factory()->create(['views' => 998, 'downloads' => 998]);
        $viewedMovie->tags()->attach($likedTag->id);
        UserJavHistory::query()->create([
            'user_id' => $user->id,
            'jav_id' => $viewedMovie->id,
            'action' => 'view',
        ]);

        $matchHigh = Jav::factory()->create(['views' => 50, 'downloads' => 20]);
        $matchHigh->actors()->attach($likedActor->id);

        $matchLow = Jav::factory()->create(['views' => 20, 'downloads' => 10]);
        $matchLow->tags()->attach($likedTag->id);

        $service = app(RecommendationService::class);
        $recommendations = $service->getRecommendations($user, 10);

        $this->assertCount(2, $recommendations);
        $this->assertSame($matchHigh->id, $recommendations[0]->id);
        $this->assertSame($matchLow->id, $recommendations[1]->id);
        $this->assertFalse($recommendations->pluck('id')->contains($likedMovie->id));
        $this->assertFalse($recommendations->pluck('id')->contains($viewedMovie->id));
    }

    public function test_sync_snapshots_for_users_by_jav_syncs_only_related_users_with_actor_or_tag_likes(): void
    {
        $relatedUser = User::factory()->create();
        $relatedByTag = User::factory()->create();
        $unrelatedUser = User::factory()->create();

        $actor = Actor::factory()->create();
        $tag = Tag::factory()->create();
        $jav = Jav::factory()->create();
        $jav->actors()->attach($actor->id);
        $jav->tags()->attach($tag->id);

        Favorite::query()->create([
            'user_id' => $relatedUser->id,
            'favoritable_id' => $actor->id,
            'favoritable_type' => Actor::class,
        ]);
        Favorite::query()->create([
            'user_id' => $relatedByTag->id,
            'favoritable_id' => $tag->id,
            'favoritable_type' => Tag::class,
        ]);
        Favorite::query()->create([
            'user_id' => $unrelatedUser->id,
            'favoritable_id' => Jav::factory()->create()->id,
            'favoritable_type' => Jav::class,
        ]);

        $service = app(RecommendationService::class);
        $synced = $service->syncSnapshotsForUsersByJav($jav, 5);

        $this->assertSame(2, $synced);
    }

    public function test_sync_snapshot_for_user_id_returns_false_when_user_does_not_exist(): void
    {
        $service = app(RecommendationService::class);

        $this->assertFalse($service->syncSnapshotForUserId(999999, 10));
    }

    public function test_sync_snapshots_for_users_by_jav_returns_zero_when_jav_has_no_actors_or_tags(): void
    {
        $jav = Jav::factory()->create();

        $service = app(RecommendationService::class);
        $synced = $service->syncSnapshotsForUsersByJav($jav, 10);

        $this->assertSame(0, $synced);
    }

    public function test_get_recommendations_with_reasons_caps_match_lists_to_two_items(): void
    {
        $user = User::factory()->create();
        $actors = Actor::factory()->count(3)->create();
        $tags = Tag::factory()->count(3)->create();

        foreach ($actors as $actor) {
            Favorite::query()->create([
                'user_id' => $user->id,
                'favoritable_id' => $actor->id,
                'favoritable_type' => Actor::class,
            ]);
        }

        foreach ($tags as $tag) {
            Favorite::query()->create([
                'user_id' => $user->id,
                'favoritable_id' => $tag->id,
                'favoritable_type' => Tag::class,
            ]);
        }

        $recommended = Jav::factory()->create(['views' => 100, 'downloads' => 50]);
        $recommended->actors()->attach($actors->pluck('id')->all());
        $recommended->tags()->attach($tags->pluck('id')->all());

        $service = app(RecommendationService::class);
        $entries = $service->getRecommendationsWithReasons($user, 10);

        $this->assertNotEmpty($entries);
        $first = $entries->first();

        $this->assertIsArray($first);
        $this->assertArrayHasKey('movie', $first);
        $this->assertArrayHasKey('reasons', $first);
        $this->assertLessThanOrEqual(2, count($first['reasons']['actors'] ?? []));
        $this->assertLessThanOrEqual(2, count($first['reasons']['tags'] ?? []));
    }
}
