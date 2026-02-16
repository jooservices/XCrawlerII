<?php

namespace Modules\JAV\Tests\Unit\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Modules\JAV\Events\RelatedCacheHit;
use Modules\JAV\Events\RelatedCacheMiss;
use Modules\JAV\Events\RelatedCacheWarmed;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Services\SearchService;
use Modules\JAV\Tests\TestCase;

class SearchServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Jav::disableSearchSyncing();
        Tag::disableSearchSyncing();
        Actor::disableSearchSyncing();
    }

    public function test_related_by_actors_cache_miss_then_hit(): void
    {
        Event::fake([RelatedCacheHit::class, RelatedCacheMiss::class, RelatedCacheWarmed::class]);

        $jav = Jav::factory()->create();
        $actor = Actor::factory()->create(['name' => 'A']);

        $jav->actors()->attach($actor->id);

        $service = app(SearchService::class);
        $cacheKey = 'jav:related:actors:'.$jav->id.':v1';
        Cache::forget($cacheKey);

        $service->getRelatedByActors($jav->fresh('actors'), 10);
        $this->assertTrue(Cache::has($cacheKey));
        Event::assertDispatched(RelatedCacheMiss::class);
        Event::assertDispatched(RelatedCacheWarmed::class);

        $expected = collect([(object) ['id' => 999_001]]);
        Cache::put($cacheKey, $expected, now()->addMinutes(10));

        $second = $service->getRelatedByActors($jav->fresh('actors'), 10);
        $this->assertEquals($expected, $second);
        Event::assertDispatched(RelatedCacheHit::class);
    }

    public function test_related_by_tags_cache_miss_then_hit(): void
    {
        Event::fake([RelatedCacheHit::class, RelatedCacheMiss::class, RelatedCacheWarmed::class]);

        $jav = Jav::factory()->create();
        $tag = Tag::factory()->create(['name' => 'T']);

        $jav->tags()->attach($tag->id);

        $service = app(SearchService::class);
        $cacheKey = 'jav:related:tags:'.$jav->id.':v1';
        Cache::forget($cacheKey);

        $service->getRelatedByTags($jav->fresh('tags'), 10);
        $this->assertTrue(Cache::has($cacheKey));
        Event::assertDispatched(RelatedCacheMiss::class);
        Event::assertDispatched(RelatedCacheWarmed::class);

        $expected = collect([(object) ['id' => 999_002]]);
        Cache::put($cacheKey, $expected, now()->addMinutes(10));

        $second = $service->getRelatedByTags($jav->fresh('tags'), 10);
        $this->assertEquals($expected, $second);
        Event::assertDispatched(RelatedCacheHit::class);
    }

    public function test_related_by_actors_excludes_self_and_empty_returns_empty(): void
    {
        $jav = Jav::factory()->create();
        $actor = Actor::factory()->create(['name' => 'Only']);
        $jav->actors()->attach($actor->id);

        $service = app(SearchService::class);
        $result = $service->getRelatedByActors($jav->fresh('actors'), 10);
        $this->assertFalse($result->contains('id', $jav->id));

        $noActors = Jav::factory()->create();
        $this->assertCount(0, $service->getRelatedByActors($noActors->fresh('actors'), 10));
    }

    public function test_related_by_tags_excludes_self_and_empty_returns_empty(): void
    {
        $jav = Jav::factory()->create();
        $tag = Tag::factory()->create(['name' => 'Only']);
        $jav->tags()->attach($tag->id);

        $service = app(SearchService::class);
        $result = $service->getRelatedByTags($jav->fresh('tags'), 10);
        $this->assertFalse($result->contains('id', $jav->id));

        $noTags = Jav::factory()->create();
        $this->assertCount(0, $service->getRelatedByTags($noTags->fresh('tags'), 10));
    }
}
