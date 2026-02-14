<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Modules\JAV\Services\OnejavService;
use Modules\JAV\Tests\TestCase;

class SyncTagsCommandTest extends TestCase
{
    public function test_command_syncs_tags_for_source()
    {
        $service = \Mockery::mock(OnejavService::class);
        $service->shouldReceive('tags')
            ->once()
            ->andReturn(collect(['16HR+', '4K']));
        $this->app->instance(OnejavService::class, $service);

        $this->artisan('jav:sync', ['provider' => 'onejav', '--type' => 'tags'])
            ->assertExitCode(0);
    }

    public function test_command_rejects_invalid_source()
    {
        $this->artisan('jav:sync', ['provider' => 'invalid', '--type' => 'tags'])
            ->assertExitCode(2);
    }
}
