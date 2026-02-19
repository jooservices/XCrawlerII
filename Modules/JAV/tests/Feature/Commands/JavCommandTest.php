<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Illuminate\Support\Facades\Queue;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Jobs\OnejavJob;
use Modules\JAV\Jobs\TagsSyncJob;
use Modules\JAV\Services\ActorProfileUpsertService;
use Modules\JAV\Services\Clients\XcityClient;
use Modules\JAV\Services\XcityIdolService;
use Modules\JAV\Tests\TestCase;

class JavCommandTest extends TestCase
{
    public function test_command_without_type_runs_default_flows(): void
    {
        Queue::fake();

        $this->artisan('jav:sync', [
            '--only' => ['content'],
            '--provider' => ['onejav'],
        ])
            ->assertExitCode(0);

        Queue::assertPushedOn('onejav', DailySyncJob::class, function ($job) {
            return $job->source === 'onejav' && $job->page === 1;
        });

        Queue::assertPushedOn('onejav', OnejavJob::class, function ($job) {
            return $job->type === 'popular';
        });

        Queue::assertPushedOn('onejav', TagsSyncJob::class, function (TagsSyncJob $job) {
            return $job->source === 'onejav';
        });
    }

    public function test_command_rejects_invalid_only_component(): void
    {
        Queue::fake();

        $this->artisan('jav:sync', [
            '--only' => ['invalid-component'],
        ])->assertExitCode(2);

        Queue::assertNothingPushed();
    }

    public function test_command_rejects_search_reset_without_confirm_flag(): void
    {
        $this->artisan('jav:sync', [
            '--only' => ['search'],
            '--search-mode' => 'reset',
        ])->assertExitCode(2);
    }

    public function test_command_can_dispatch_idol_sync_component(): void
    {
        Queue::fake();

        $service = $this->buildRealIdolServiceFromFixtures();
        $this->app->instance(XcityIdolService::class, $service);

        $this->artisan('jav:sync', [
            '--only' => ['idols'],
            '--concurrency' => 2,
        ])->assertExitCode(0);

        Queue::assertPushed(\Modules\JAV\Jobs\XcityKanaSyncJob::class, 2);
    }

    public function test_command_can_run_recommendations_component_for_specific_users(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->artisan('jav:sync', [
            '--only' => ['recommendations'],
            '--user-id' => [$user->id],
            '--limit' => 15,
        ])->assertExitCode(0);
    }

    private function buildRealIdolServiceFromFixtures(): XcityIdolService
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

        return new XcityIdolService($client, new ActorProfileUpsertService);
    }
}
