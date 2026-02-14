<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Illuminate\Support\Facades\Queue;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Tests\TestCase;

class DailySyncCommandTest extends TestCase
{
    public function test_command_dispatches_daily_job_with_explicit_date()
    {
        Queue::fake();

        $this->artisan('jav:sync:content', [
            'provider' => 'onejav',
            '--type' => ['daily'],
            '--date' => '2026-02-14',
        ])->assertExitCode(0);

        Queue::assertPushedOn('jav', DailySyncJob::class, function ($job) {
            return $job->source === 'onejav'
                && $job->date === '2026-02-14'
                && $job->page === 1;
        });
    }

    public function test_command_rejects_invalid_source()
    {
        Queue::fake();

        $this->artisan('jav:sync:content', [
            'provider' => 'invalid',
            '--type' => ['daily'],
        ])->assertExitCode(2);

        Queue::assertNothingPushed();
    }
}
