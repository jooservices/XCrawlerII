<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Illuminate\Support\Facades\Queue;
use Modules\JAV\Jobs\TagsSyncJob;
use Modules\JAV\Tests\TestCase;

class SyncTagsCommandTest extends TestCase
{
    public function test_command_syncs_tags_for_source()
    {
        Queue::fake();

        $this->artisan('jav:sync:content', ['provider' => 'onejav', '--type' => ['tags']])
            ->assertExitCode(0);

        Queue::assertPushedOn('onejav', TagsSyncJob::class, function (TagsSyncJob $job): bool {
            return $job->source === 'onejav';
        });
    }

    public function test_command_rejects_invalid_source()
    {
        $this->artisan('jav:sync:content', ['provider' => 'invalid', '--type' => ['tags']])
            ->assertExitCode(2);
    }
}
