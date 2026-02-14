<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Jobs\OnejavJob;
use Modules\JAV\Services\OnejavService;
use Modules\JAV\Tests\TestCase;

class JavCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_without_type_runs_default_flows(): void
    {
        Queue::fake();

        $service = \Mockery::mock(OnejavService::class);
        $service->shouldReceive('tags')
            ->once()
            ->andReturn(collect(['4K', '16HR+']));
        $this->app->instance(OnejavService::class, $service);

        $this->artisan('jav:sync', [
            '--only' => ['content'],
            '--provider' => ['onejav'],
        ])
            ->assertExitCode(0);

        Queue::assertPushedOn('jav', DailySyncJob::class, function ($job) {
            return $job->source === 'onejav' && $job->page === 1;
        });

        Queue::assertPushedOn('jav', OnejavJob::class, function ($job) {
            return $job->type === 'popular';
        });
    }
}
