<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Modules\JAV\Jobs\TagsSyncJob;
use Modules\JAV\Models\Tag;
use Modules\JAV\Services\Clients\OnejavClient;
use Modules\JAV\Services\CrawlerPaginationStateService;
use Modules\JAV\Services\CrawlerResponseCacheService;
use Modules\JAV\Services\CrawlerStatusPolicyService;
use Modules\JAV\Services\OnejavService;
use Modules\JAV\Tests\TestCase;

class TagsSyncJobTest extends TestCase
{
    public function test_unique_id_is_based_on_source(): void
    {
        $job = new TagsSyncJob('onejav');

        $this->assertEquals('onejav', $job->uniqueId());
    }

    public function test_handle_calls_tags_on_resolved_service(): void
    {
        $client = \Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/tag')
            ->andReturn($this->getMockResponse('onejav_tags_minimal.html'));
        $this->app->instance(OnejavService::class, new OnejavService(
            $client,
            app(CrawlerResponseCacheService::class),
            app(CrawlerPaginationStateService::class),
            app(CrawlerStatusPolicyService::class)
        ));

        $job = new TagsSyncJob('onejav');
        $job->handle();

        $this->assertDatabaseHas((new Tag)->getTable(), ['name' => '4K']);
        $this->assertDatabaseHas((new Tag)->getTable(), ['name' => 'VR']);
        $this->assertSame(2, Tag::query()->whereIn('name', ['4K', 'VR'])->count());
    }
}
