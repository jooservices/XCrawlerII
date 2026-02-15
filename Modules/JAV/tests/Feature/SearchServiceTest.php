<?php

namespace Modules\JAV\Tests\Feature;

use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Services\SearchService;
use Modules\JAV\Tests\TestCase;

class SearchServiceTest extends TestCase
{
    protected SearchService $searchService;

    protected function setUp(): void
    {
        putenv('SCOUT_DRIVER=collection');
        parent::setUp();
        // Force collection driver for testing if not already set
        config(['scout.driver' => 'collection']);

        $this->searchService = app(SearchService::class);
    }

    public function test_can_search_tags_and_get_correct_count()
    {
        $tag = Tag::create(['name' => 'Time Stop']);
        $jav = Jav::create([
            'url' => $this->faker->url,
            'title' => $this->faker->sentence,
            'source' => 'onejav',
        ]);
        $jav->tags()->attach($tag);

        $results = $this->searchService->searchTags('Time Stop');

        $this->assertCount(1, $results->items());
        // Fix for potential lint/property access
        $first = $results->items()[0] ?? null;
        $this->assertNotNull($first);
        $this->assertEquals(1, $first->javs_count);
    }
}
