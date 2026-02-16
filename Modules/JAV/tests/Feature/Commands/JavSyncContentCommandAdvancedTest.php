<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Carbon\Carbon;
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

        Queue::assertPushedOn('onejav', OnejavJob::class, function (OnejavJob $job): bool {
            return $job->type === 'new';
        });

        Queue::assertPushedOn('onejav', OnejavJob::class, function (OnejavJob $job): bool {
            return $job->type === 'popular';
        });

        Queue::assertPushedOn('onejav', DailySyncJob::class, function (DailySyncJob $job): bool {
            return $job->source === 'onejav' && $job->page === 1;
        });

        Queue::assertPushedOn('onejav', TagsSyncJob::class, function (TagsSyncJob $job): bool {
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

    public function test_command_deduplicates_repeated_types_before_dispatching(): void
    {
        Queue::fake();

        $this->artisan('jav:sync:content', [
            'provider' => 'onejav',
            '--type' => ['new', 'new', 'daily', 'daily', 'tags'],
        ])->assertExitCode(0);

        Queue::assertPushed(OnejavJob::class, 1);
        Queue::assertPushed(DailySyncJob::class, 1);
        Queue::assertPushed(TagsSyncJob::class, 1);
    }

    public function test_command_daily_without_date_uses_current_day(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-02-15 10:00:00');

        try {
            $this->artisan('jav:sync:content', [
                'provider' => 'onejav',
                '--type' => ['daily'],
            ])->assertExitCode(0);
        } finally {
            Carbon::setTestNow();
        }

        Queue::assertPushed(DailySyncJob::class, function (DailySyncJob $job): bool {
            return $job->source === 'onejav'
                && $job->date === '2026-02-15'
                && $job->page === 1;
        });
    }

    public function test_command_returns_failure_when_daily_date_is_invalid(): void
    {
        Queue::fake();

        $this->artisan('jav:sync:content', [
            'provider' => 'onejav',
            '--type' => ['daily'],
            '--date' => 'not-a-date',
        ])->assertExitCode(2);

        Queue::assertNothingPushed();
    }
}
