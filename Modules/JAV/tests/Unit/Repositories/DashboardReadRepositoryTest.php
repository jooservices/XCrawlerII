<?php

namespace Modules\JAV\Tests\Unit\Repositories;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Repositories\ActorRepository;
use Modules\JAV\Repositories\DashboardReadRepository;
use Modules\JAV\Repositories\FavoriteRepository;
use Modules\JAV\Repositories\JavRepository;
use Modules\JAV\Repositories\RatingRepository;
use Modules\JAV\Repositories\TagRepository;
use Modules\JAV\Repositories\UserJavHistoryRepository;
use Modules\JAV\Repositories\UserLikeNotificationRepository;
use Modules\JAV\Repositories\WatchlistRepository;
use Modules\JAV\Services\SearchService;
use Tests\TestCase;

class DashboardReadRepositoryTest extends TestCase
{
    public function test_search_with_preset_default_delegates_to_search_service(): void
    {
        $paginator = new Paginator(collect(), 0, 30);
        $search = Mockery::mock(SearchService::class);
        $search->shouldReceive('searchJav')
            ->once()
            ->with('q', ['a' => 'b'], 30, 'created_at', 'desc')
            ->andReturn($paginator);

        $repository = $this->makeRepository(searchService: $search);
        $result = $repository->searchWithPreset('q', ['a' => 'b'], 30, 'created_at', 'desc', 'default');

        $this->assertSame($paginator, $result);
    }

    public function test_decorate_items_for_user_sets_like_watchlist_and_rating_fields(): void
    {
        $user = Mockery::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn(10);

        $items = collect([(object) ['id' => 1], (object) ['id' => 2]]);
        $paginator = new Paginator($items, 2, 30);

        $favoriteRepo = Mockery::mock(FavoriteRepository::class);
        $favoriteRepo->shouldReceive('likedJavIdsForUserAndJavIds')
            ->once()
            ->andReturn(collect([1 => 0]));

        $watchlistRepo = Mockery::mock(WatchlistRepository::class);
        $watchlistRepo->shouldReceive('keyedByJavIdForUserAndJavIds')
            ->once()
            ->andReturn(collect([1 => (object) ['id' => 101]]));

        $ratingRepo = Mockery::mock(RatingRepository::class);
        $ratingRepo->shouldReceive('keyedByJavIdForUserAndJavIds')
            ->once()
            ->andReturn(collect([1 => (object) ['id' => 201, 'rating' => 5]]));

        $repository = $this->makeRepository(
            favoriteRepository: $favoriteRepo,
            watchlistRepository: $watchlistRepo,
            ratingRepository: $ratingRepo,
        );

        $repository->decorateItemsForUser($paginator, $user);

        $collection = $paginator->getCollection();
        $first = $collection->first();
        $second = $collection->last();

        $this->assertTrue($first->is_liked);
        $this->assertSame(101, $first->watchlist_id);
        $this->assertTrue($first->in_watchlist);
        $this->assertSame(5, $first->user_rating);
        $this->assertSame(201, $first->user_rating_id);

        $this->assertFalse($second->is_liked);
        $this->assertNull($second->watchlist_id);
        $this->assertFalse($second->in_watchlist);
        $this->assertNull($second->user_rating);
        $this->assertNull($second->user_rating_id);
    }

    public function test_passthrough_methods_delegate_to_underlying_repositories(): void
    {
        $user = Mockery::mock(Authenticatable::class);
        $actor = Mockery::mock(Actor::class);
        $jav = Mockery::mock(Jav::class);
        $paginator = new Paginator(collect(), 0, 30);
        $collection = collect([1, 2, 3]);

        $search = Mockery::mock(SearchService::class);
        $search->shouldReceive('searchActors')->once()->with('amy')->andReturn($paginator);
        $search->shouldReceive('searchTags')->once()->with('idol')->andReturn($paginator);

        $history = Mockery::mock(UserJavHistoryRepository::class);
        $history->shouldReceive('continueWatching')->once()->with(1, 8)->andReturn($collection);
        $history->shouldReceive('paginateForUser')->once()->with(1, 30)->andReturn($paginator);

        $actorRepo = Mockery::mock(ActorRepository::class);
        $actorRepo->shouldReceive('actorMovies')->once()->with($actor, 30)->andReturn($paginator);

        $javRepo = Mockery::mock(JavRepository::class);
        $javRepo->shouldReceive('loadRelations')->once()->with($jav)->andReturn($jav);

        $favoriteRepo = Mockery::mock(FavoriteRepository::class);
        $favoriteRepo->shouldReceive('isJavLikedByUser')->once()->with($jav, 1)->andReturn(true);
        $favoriteRepo->shouldReceive('paginateForUser')->once()->with(1, 30)->andReturn($paginator);

        $notifications = Mockery::mock(UserLikeNotificationRepository::class);
        $notifications->shouldReceive('unreadForUser')->once()->with($user, 20)->andReturn(collect());
        $notifications->shouldReceive('markAllReadForUser')->once()->with($user)->andReturn(2);

        $repository = $this->makeRepository(
            searchService: $search,
            javRepository: $javRepo,
            actorRepository: $actorRepo,
            favoriteRepository: $favoriteRepo,
            historyRepository: $history,
            notificationRepository: $notifications,
        );

        $this->assertSame($collection, $repository->continueWatching(1, 8));
        $this->assertSame($paginator, $repository->actorMovies($actor, 30));
        $this->assertSame($paginator, $repository->searchActors('amy'));
        $this->assertSame($paginator, $repository->searchTags('idol'));
        $this->assertSame($jav, $repository->loadJavRelations($jav));
        $this->assertTrue($repository->isJavLikedByUser($jav, 1));
        $this->assertSame($paginator, $repository->historyForUser(1, 30));
        $this->assertSame($paginator, $repository->favoritesForUser(1, 30));
        $this->assertEquals(collect(), $repository->unreadNotificationsForUser($user, 20));
        $this->assertSame(2, $repository->markAllNotificationsReadForUser($user));
    }

    public function test_actor_and_tag_suggestions_are_cached(): void
    {
        Cache::flush();

        $actorRepo = Mockery::mock(ActorRepository::class);
        $actorRepo->shouldReceive('suggestions')->once()->with(500)->andReturn(['A']);

        $tagRepo = Mockery::mock(TagRepository::class);
        $tagRepo->shouldReceive('suggestions')->once()->with(700)->andReturn(['T']);

        $repository = $this->makeRepository(
            actorRepository: $actorRepo,
            tagRepository: $tagRepo,
        );

        $this->assertSame(['A'], $repository->actorSuggestions());
        $this->assertSame(['A'], $repository->actorSuggestions());
        $this->assertSame(['T'], $repository->tagSuggestions());
        $this->assertSame(['T'], $repository->tagSuggestions());
    }

    private function makeRepository(
        ?SearchService $searchService = null,
        ?JavRepository $javRepository = null,
        ?ActorRepository $actorRepository = null,
        ?TagRepository $tagRepository = null,
        ?FavoriteRepository $favoriteRepository = null,
        ?UserJavHistoryRepository $historyRepository = null,
        ?WatchlistRepository $watchlistRepository = null,
        ?RatingRepository $ratingRepository = null,
        ?UserLikeNotificationRepository $notificationRepository = null,
    ): DashboardReadRepository {
        return new DashboardReadRepository(
            $searchService ?? Mockery::mock(SearchService::class),
            $javRepository ?? Mockery::mock(JavRepository::class),
            $actorRepository ?? Mockery::mock(ActorRepository::class),
            $tagRepository ?? Mockery::mock(TagRepository::class),
            $favoriteRepository ?? Mockery::mock(FavoriteRepository::class),
            $historyRepository ?? Mockery::mock(UserJavHistoryRepository::class),
            $watchlistRepository ?? Mockery::mock(WatchlistRepository::class),
            $ratingRepository ?? Mockery::mock(RatingRepository::class),
            $notificationRepository ?? Mockery::mock(UserLikeNotificationRepository::class),
        );
    }
}
