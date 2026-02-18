<?php

namespace Modules\JAV\Tests\Unit\Repositories;

use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Cache;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Models\UserJavHistory;
use Modules\JAV\Models\UserLikeNotification;
use Modules\JAV\Models\Watchlist;
use Modules\JAV\Repositories\DashboardReadRepository;
use Modules\JAV\Tests\TestCase;

class DashboardReadRepositoryTest extends TestCase
{
    public function test_search_with_preset_default_returns_query_matches(): void
    {
        config(['scout.driver' => 'collection']);

        $matched = Jav::factory()->create(['title' => 'Alpha Match']);
        Jav::factory()->create(['title' => 'Beta']);

        $repository = app(DashboardReadRepository::class);
        $result = $repository->searchWithPreset('Alpha', [], 30, 'created_at', 'desc', 'default');

        $this->assertCount(1, $result->items());
        $this->assertSame($matched->id, $result->items()[0]->id);
    }

    public function test_search_with_preset_weekly_downloads_excludes_old_items_and_sorts_desc(): void
    {
        config(['scout.driver' => 'collection']);

        $recentHigh = Jav::factory()->create([
            'downloads' => 500,
            'created_at' => now()->subDays(2),
        ]);
        $recentLow = Jav::factory()->create([
            'downloads' => 50,
            'created_at' => now()->subDays(3),
        ]);
        Jav::factory()->create([
            'downloads' => 999,
            'created_at' => now()->subDays(12),
        ]);

        $repository = app(DashboardReadRepository::class);
        $result = $repository->searchWithPreset('', [], 30, null, 'asc', 'weekly_downloads');

        $this->assertCount(2, $result->items());
        $this->assertSame($recentHigh->id, $result->items()[0]->id);
        $this->assertSame($recentLow->id, $result->items()[1]->id);
    }

    public function test_search_with_preset_preferred_tags_respects_user_preference_favorites(): void
    {
        config(['scout.driver' => 'collection']);

        $user = $this->createUser();
        $preferredTag = Tag::factory()->create(['name' => 'Preferred']);
        $otherTag = Tag::factory()->create(['name' => 'Other']);

        Interaction::factory()->forTag($preferredTag)->favorite()->create(['user_id' => $user->id]);

        $preferredMovie = Jav::factory()->create();
        $preferredMovie->tags()->attach($preferredTag->id);

        $otherMovie = Jav::factory()->create();
        $otherMovie->tags()->attach($otherTag->id);

        $repository = app(DashboardReadRepository::class);
        $result = $repository->searchWithPreset('', [], 30, null, 'desc', 'preferred_tags', $this->asAuthenticatable($user));

        $this->assertCount(1, $result->items());
        $this->assertSame($preferredMovie->id, $result->items()[0]->id);
        $this->assertNotSame($preferredMovie->id, $otherMovie->id);
    }

    public function test_decorate_items_for_user_sets_like_watchlist_and_rating_flags(): void
    {
        $user = $this->createUser();
        $likedJav = Jav::factory()->create();
        $plainJav = Jav::factory()->create();

        Interaction::factory()->forJav($likedJav)->favorite()->create(['user_id' => $user->id]);

        $watchlist = Watchlist::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $likedJav->id,
        ]);

        $rating = Interaction::factory()
            ->forJav($likedJav)
            ->rating(5)
            ->create(['user_id' => $user->id]);

        $items = collect([$likedJav, $plainJav]);
        $paginator = new Paginator($items, 2, 30);

        $repository = app(DashboardReadRepository::class);
        $repository->decorateItemsForUser($paginator, $this->asAuthenticatable($user));

        $first = $paginator->items()[0];
        $second = $paginator->items()[1];

        $this->assertTrue($first->is_liked);
        $this->assertTrue($first->in_watchlist);
        $this->assertSame($watchlist->id, $first->watchlist_id);
        $this->assertSame(5, $first->user_rating);
        $this->assertSame($rating->id, $first->user_rating_id);

        $this->assertFalse($second->is_liked);
        $this->assertFalse($second->in_watchlist);
        $this->assertNull($second->watchlist_id);
        $this->assertNull($second->user_rating);
        $this->assertNull($second->user_rating_id);
    }

    public function test_passthrough_methods_return_expected_records(): void
    {
        config(['scout.driver' => 'collection']);

        $user = $this->createUser();
        $jav = Jav::factory()->create(['title' => 'Needle Movie']);
        $actor = Actor::factory()->create(['name' => 'Alice Doe']);
        $tag = Tag::factory()->create(['name' => 'Idol']);

        $jav->actors()->attach($actor->id);
        $jav->tags()->attach($tag->id);

        Interaction::factory()->forJav($jav)->favorite()->create(['user_id' => $user->id]);

        UserJavHistory::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'action' => 'view',
            'updated_at' => now()->subMinute(),
        ]);

        UserLikeNotification::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'read_at' => null,
        ]);

        $repository = app(DashboardReadRepository::class);

        $this->assertCount(1, $repository->continueWatching($user->id, 8));
        $this->assertCount(1, $repository->actorMovies($actor, 30)->items());
        $this->assertCount(1, $repository->searchActors('Alice')->items());
        $this->assertCount(1, $repository->searchTags('Idol')->items());

        $loaded = $repository->loadJavRelations($jav->fresh());
        $this->assertTrue($loaded->relationLoaded('actors'));
        $this->assertTrue($loaded->relationLoaded('tags'));

        $this->assertTrue($repository->isJavLikedByUser($jav, $user->id));
        $this->assertCount(1, $repository->historyForUser($user->id, 30)->items());
        $this->assertCount(1, $repository->favoritesForUser($user->id, 30)->items());

        $unread = $repository->unreadNotificationsForUser($this->asAuthenticatable($user), 20);
        $this->assertCount(1, $unread);

        $marked = $repository->markAllNotificationsReadForUser($this->asAuthenticatable($user));
        $this->assertSame(1, $marked);
    }

    public function test_actor_and_tag_suggestions_are_cached(): void
    {
        Cache::flush();

        Actor::factory()->create(['name' => '  Alice  ']);
        Tag::factory()->create(['name' => '  Idol  ']);

        $repository = app(DashboardReadRepository::class);

        $firstActors = $repository->actorSuggestions();
        $firstTags = $repository->tagSuggestions();

        Actor::factory()->create(['name' => 'New Actor']);
        Tag::factory()->create(['name' => 'New Tag']);

        $secondActors = $repository->actorSuggestions();
        $secondTags = $repository->tagSuggestions();

        $this->assertSame($firstActors, $secondActors);
        $this->assertSame($firstTags, $secondTags);
        $this->assertContains('Alice', $firstActors);
        $this->assertContains('Idol', $firstTags);
    }
}
