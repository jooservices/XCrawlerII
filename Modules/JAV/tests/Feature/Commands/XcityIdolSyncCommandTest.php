<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Illuminate\Support\Facades\Queue;
use Modules\JAV\Jobs\XcityKanaSyncJob;
use Modules\JAV\Services\ActorProfileUpsertService;
use Modules\JAV\Services\Clients\XcityClient;
use Modules\JAV\Services\CrawlerResponseCacheService;
use Modules\JAV\Services\XcityIdolService;
use Modules\JAV\Tests\TestCase;

class XcityIdolSyncCommandTest extends TestCase
{
    public function test_command_dispatches_kana_jobs_based_on_concurrency(): void
    {
        Queue::fake();

        $service = $this->buildRealServiceFromFixtures();
        $this->app->instance(XcityIdolService::class, $service);

        $this->artisan('jav:sync:idols', ['--concurrency' => 2])
            ->assertExitCode(0);

        Queue::assertPushed(XcityKanaSyncJob::class, 2);
        Queue::assertPushedOn('xcity', XcityKanaSyncJob::class);
    }

    private function buildRealServiceFromFixtures(): XcityIdolService
    {
        $client = \Mockery::mock(XcityClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/idol/')
            ->andReturn($this->getMockResponse('xcity_root_with_kana.html'));
        $client->shouldReceive('get')
            ->withArgs(function (string $url): bool {
                return str_contains($url, 'https://xxx.xcity.jp/idol/?kana=');
            })
            ->andReturnUsing(function (string $url) {
                if (str_contains($url, 'kana=%E3%81%8B')) {
                    return $this->getMockResponse('xcity_kana_ka_with_ini.html');
                }

                if (str_contains($url, 'kana=%E3%81%95')) {
                    return $this->getMockResponse('xcity_kana_sa_without_ini.html');
                }

                return $this->getMockResponse('xcity_kana_sa_without_ini.html');
            });

        return new XcityIdolService($client, app(CrawlerResponseCacheService::class), new ActorProfileUpsertService);
    }
}
