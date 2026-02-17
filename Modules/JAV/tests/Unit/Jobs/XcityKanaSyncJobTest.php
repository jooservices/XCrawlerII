<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Bus;
use Modules\JAV\Jobs\XcityKanaSyncJob;
use Modules\JAV\Services\ActorProfileUpsertService;
use Modules\JAV\Services\Clients\XcityClient;
use Modules\JAV\Services\CrawlerResponseCacheService;
use Modules\JAV\Services\XcityIdolService;
use Modules\JAV\Tests\TestCase;

class XcityKanaSyncJobTest extends TestCase
{
    public function test_unique_id_is_prefixed_with_xcity_seed_key(): void
    {
        $job = new XcityKanaSyncJob('kana-a', 'https://xxx.xcity.jp/idol/?kana=a');

        $this->assertSame('xcity:kana-a', $job->uniqueId());
    }

    public function test_handle_passes_default_queue_name_to_service(): void
    {
        Bus::fake();
        config(['jav.idol_queue' => 'jav-idol-default-test']);

        $service = $this->buildServiceForKanaUrl('https://xxx.xcity.jp/idol/?kana=a');

        $job = new XcityKanaSyncJob('kana-a', 'https://xxx.xcity.jp/idol/?kana=a');
        $job->handle($service);

        Bus::assertBatched(function ($batch): bool {
            return $batch->queue() === 'jav-idol-default-test';
        });
    }

    public function test_handle_passes_explicit_queue_name_to_service_when_set(): void
    {
        Bus::fake();

        $service = $this->buildServiceForKanaUrl('https://xxx.xcity.jp/idol/?kana=b');

        $job = new XcityKanaSyncJob('kana-b', 'https://xxx.xcity.jp/idol/?kana=b');
        $job->onQueue('xcity');
        $job->handle($service);

        Bus::assertBatched(function ($batch): bool {
            return $batch->queue() === 'xcity';
        });
    }

    private function buildServiceForKanaUrl(string $url): XcityIdolService
    {
        $client = \Mockery::mock(XcityClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with($url)
            ->andReturn($this->getMockResponse('xcity_idol_list_page_1.html'));

        return new XcityIdolService($client, app(CrawlerResponseCacheService::class), new ActorProfileUpsertService);
    }
}
