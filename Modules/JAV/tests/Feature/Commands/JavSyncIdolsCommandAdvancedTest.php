<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Illuminate\Support\Facades\Queue;
use Modules\Core\Facades\Config;
use Modules\JAV\Jobs\XcityKanaSyncJob;
use Modules\JAV\Services\ActorProfileUpsertService;
use Modules\JAV\Services\Clients\XcityClient;
use Modules\JAV\Services\CrawlerResponseCacheService;
use Modules\JAV\Services\XcityIdolService;
use Modules\JAV\Tests\TestCase;

class JavSyncIdolsCommandAdvancedTest extends TestCase
{
    public function test_command_rejects_invalid_source(): void
    {
        $this->artisan('jav:sync:idols', [
            '--source' => 'invalid',
        ])->assertExitCode(2);
    }

    public function test_command_returns_success_when_all_seeds_are_currently_running(): void
    {
        Queue::fake();

        $service = $this->buildRealServiceFromFixtures();
        $seeds = $service->seedKanaUrls();
        foreach (array_keys($seeds) as $seedKey) {
            Config::set('xcity', "kana_{$seedKey}_running", '1');
        }

        $this->app->instance(XcityIdolService::class, $service);

        $this->artisan('jav:sync:idols')->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_command_honors_queue_option_when_dispatching_jobs(): void
    {
        Queue::fake();

        $service = $this->buildRealServiceFromFixtures();
        $this->app->instance(XcityIdolService::class, $service);

        $this->artisan('jav:sync:idols', [
            '--queue' => 'xcity',
            '--concurrency' => 1,
        ])->assertExitCode(0);

        Queue::assertPushedOn('xcity', XcityKanaSyncJob::class);
        Queue::assertPushed(XcityKanaSyncJob::class, 1);
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
