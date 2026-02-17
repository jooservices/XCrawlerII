<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Cache;
use Modules\JAV\Jobs\XcityPersistIdolProfileJob;
use Modules\JAV\Services\ActorProfileUpsertService;
use Modules\JAV\Services\Clients\XcityClient;
use Modules\JAV\Services\CrawlerResponseCacheService;
use Modules\JAV\Services\XcityIdolService;
use Modules\JAV\Tests\TestCase;

class XcityPersistIdolProfileJobTest extends TestCase
{
    public function test_job_uses_batchable_trait(): void
    {
        $traits = class_uses_recursive(XcityPersistIdolProfileJob::class);

        $this->assertContains(Batchable::class, $traits);
    }

    public function test_handle_persists_profile_and_caches_index_flag(): void
    {
        $client = \Mockery::mock(XcityClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('https://xxx.xcity.jp/idol/detail/1001/')
            ->andReturn($this->getMockResponse('xcity_idol_detail_5750.html'));

        $service = new XcityIdolService($client, app(CrawlerResponseCacheService::class), new ActorProfileUpsertService);

        $job = new XcityPersistIdolProfileJob(
            xcityId: '1001',
            name: 'Airi Kijima',
            detailUrl: 'https://xxx.xcity.jp/idol/detail/1001/',
            coverImage: 'https://example.com/cover.jpg'
        );

        $job->handle($service);

        $this->assertTrue((bool) Cache::get('xcity:index_actor:1001'));
    }
}
