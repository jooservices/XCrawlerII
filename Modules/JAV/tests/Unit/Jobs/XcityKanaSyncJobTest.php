<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Mockery;
use Modules\JAV\Jobs\XcityKanaSyncJob;
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
        $service = Mockery::mock(XcityIdolService::class);
        $service->shouldReceive('syncKanaPage')
            ->once()
            ->with('kana-a', 'https://xxx.xcity.jp/idol/?kana=a', null);

        $job = new XcityKanaSyncJob('kana-a', 'https://xxx.xcity.jp/idol/?kana=a');
        $job->handle($service);

        $this->assertTrue(true);
    }

    public function test_handle_passes_explicit_queue_name_to_service_when_set(): void
    {
        $service = Mockery::mock(XcityIdolService::class);
        $service->shouldReceive('syncKanaPage')
            ->once()
            ->with('kana-b', 'https://xxx.xcity.jp/idol/?kana=b', 'xcity');

        $job = new XcityKanaSyncJob('kana-b', 'https://xxx.xcity.jp/idol/?kana=b');
        $job->onQueue('xcity');
        $job->handle($service);

        $this->assertTrue(true);
    }
}
