<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Illuminate\Support\Facades\Queue;
use Modules\JAV\Jobs\FfjavJob;
use Modules\JAV\Tests\TestCase;

class FfjavCommandTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Modules\JAV\Models\Jav::disableSearchSyncing();
        \Modules\JAV\Models\Tag::disableSearchSyncing();
        \Modules\JAV\Models\Actor::disableSearchSyncing();
    }

    public function test_command_dispatches_job_to_jav_queue()
    {
        Queue::fake();

        $this->artisan('jav:sync:content', ['provider' => 'ffjav', '--type' => ['new']])
            ->assertExitCode(0);

        Queue::assertPushedOn('jav', FfjavJob::class, function ($job) {
            return $job->type === 'new';
        });
    }
}
