<?php

namespace Modules\JAV\Tests\Unit\Services;

use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Services\SearchService;
use Modules\JAV\Tests\TestCase;

class SearchServiceRelatedTest extends TestCase
{
    public function test_get_related_by_actors_returns_empty_collection_when_movie_has_no_actors(): void
    {
        $jav = Jav::factory()->create();

        $service = app(SearchService::class);
        $related = $service->getRelatedByActors($jav, 10);

        $this->assertTrue($related->isEmpty());
    }

    public function test_get_related_by_tags_returns_empty_collection_when_movie_has_no_tags(): void
    {
        $jav = Jav::factory()->create();

        $service = app(SearchService::class);
        $related = $service->getRelatedByTags($jav, 10);

        $this->assertTrue($related->isEmpty());
    }

    public function test_get_related_by_actors_excludes_current_movie_and_matches_by_shared_actor(): void
    {
        $actorA = Actor::factory()->create(['name' => 'Actor A']);
        $actorB = Actor::factory()->create(['name' => 'Actor B']);

        $current = Jav::factory()->create();
        $current->actors()->attach($actorA->id);
        $current->searchable();

        $match = Jav::factory()->create();
        $match->actors()->attach($actorA->id);
        $match->searchable();

        $noMatch = Jav::factory()->create();
        $noMatch->actors()->attach($actorB->id);
        $noMatch->searchable();

        $service = app(SearchService::class);
        $related = $service->getRelatedByActors($current->fresh()->load('actors'), 10);

        $ids = $related->pluck('id')->all();
        $this->assertNotContains($current->id, $ids);
        $this->assertNotContains($noMatch->id, $ids);
    }

    public function test_get_related_by_tags_excludes_current_movie_and_matches_by_shared_tag(): void
    {
        $tagA = Tag::factory()->create(['name' => 'Tag A']);
        $tagB = Tag::factory()->create(['name' => 'Tag B']);

        $current = Jav::factory()->create();
        $current->tags()->attach($tagA->id);
        $current->searchable();

        $match = Jav::factory()->create();
        $match->tags()->attach($tagA->id);
        $match->searchable();

        $noMatch = Jav::factory()->create();
        $noMatch->tags()->attach($tagB->id);
        $noMatch->searchable();

        $service = app(SearchService::class);
        $related = $service->getRelatedByTags($current->fresh()->load('tags'), 10);

        $ids = $related->pluck('id')->all();
        $this->assertNotContains($current->id, $ids);
        $this->assertNotContains($noMatch->id, $ids);
    }

    public function test_get_related_by_actors_returns_empty_when_only_current_movie_has_the_actor(): void
    {
        $actor = Actor::factory()->create(['name' => 'Actor A']);

        $current = Jav::factory()->create(['date' => now()->subDays(10)]);
        $current->actors()->attach($actor->id);
        $current->searchable();

        $service = app(SearchService::class);
        $related = $service->getRelatedByActors($current->fresh()->load('actors'), 10);

        $this->assertTrue($related->isEmpty());
    }

    public function test_get_related_by_tags_returns_empty_when_only_current_movie_has_the_tag(): void
    {
        $tag = Tag::factory()->create(['name' => 'Tag A']);

        $current = Jav::factory()->create(['date' => now()->subDays(10)]);
        $current->tags()->attach($tag->id);
        $current->searchable();

        $service = app(SearchService::class);
        $related = $service->getRelatedByTags($current->fresh()->load('tags'), 10);

        $this->assertTrue($related->isEmpty());
    }
}
