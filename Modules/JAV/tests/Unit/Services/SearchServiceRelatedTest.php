<?php

namespace Modules\JAV\Tests\Unit\Services;

use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Services\SearchService;
use Modules\JAV\Tests\TestCase;

class SearchServiceRelatedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['scout.driver' => 'collection']);
        Jav::disableSearchSyncing();
        Actor::disableSearchSyncing();
        Tag::disableSearchSyncing();
    }

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

        $match = Jav::factory()->create();
        $match->actors()->attach($actorA->id);

        $noMatch = Jav::factory()->create();
        $noMatch->actors()->attach($actorB->id);

        $service = app(SearchService::class);
        $related = $service->getRelatedByActors($current, 10);

        $ids = $related->pluck('id')->all();
        $this->assertContains($match->id, $ids);
        $this->assertNotContains($current->id, $ids);
        $this->assertNotContains($noMatch->id, $ids);
    }

    public function test_get_related_by_tags_excludes_current_movie_and_matches_by_shared_tag(): void
    {
        $tagA = Tag::factory()->create(['name' => 'Tag A']);
        $tagB = Tag::factory()->create(['name' => 'Tag B']);

        $current = Jav::factory()->create();
        $current->tags()->attach($tagA->id);

        $match = Jav::factory()->create();
        $match->tags()->attach($tagA->id);

        $noMatch = Jav::factory()->create();
        $noMatch->tags()->attach($tagB->id);

        $service = app(SearchService::class);
        $related = $service->getRelatedByTags($current, 10);

        $ids = $related->pluck('id')->all();
        $this->assertContains($match->id, $ids);
        $this->assertNotContains($current->id, $ids);
        $this->assertNotContains($noMatch->id, $ids);
    }
}
