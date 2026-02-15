<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Illuminate\Support\Facades\Queue;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Jobs\OnejavJob;
use Modules\JAV\Jobs\TagsSyncJob;
use Modules\JAV\Tests\TestCase;

class JavSyncContentCommandAdvancedTest extends TestCase
{
    public function test_command_without_type_dispatches_all_default_jobs_for_provider(): void
    {
        Queue::fake();

        $this->artisan('jav:sync:content', [
            'provider' => 'onejav',
        ])->assertExitCode(0);

        Queue::assertPushedOn('jav', OnejavJob::class, function (OnejavJob $job): bool {
            return $job->type === 'new';
        });

        Queue::assertPushedOn('jav', OnejavJob::class, function (OnejavJob $job): bool {
            return $job->type === 'popular';
        });

        Queue::assertPushedOn('jav', DailySyncJob::class, function (DailySyncJob $job): bool {
            return $job->source === 'onejav' && $job->page === 1;
        });

        Queue::assertPushedOn('jav', TagsSyncJob::class, function (TagsSyncJob $job): bool {
            return $job->source === 'onejav';
        });
    }

    public function test_command_honors_custom_queue_option_for_dispatched_jobs(): void
    {
        Queue::fake();

        $this->artisan('jav:sync:content', [
            'provider' => 'onejav',
            '--type' => ['new', 'daily', 'tags'],
            '--queue' => 'xcity',
        ])->assertExitCode(0);

        Queue::assertPushedOn('xcity', OnejavJob::class);
        Queue::assertPushedOn('xcity', DailySyncJob::class);
        Queue::assertPushedOn('xcity', TagsSyncJob::class);
    }
}
