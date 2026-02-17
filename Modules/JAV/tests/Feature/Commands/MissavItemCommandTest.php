<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Mockery;
use Modules\JAV\Models\MissavSchedule;
use Modules\JAV\Services\MissavService;
use Modules\JAV\Tests\TestCase;

class MissavItemCommandTest extends TestCase
{
    public function test_command_uses_schedule_when_url_missing(): void
    {
        $schedule = MissavSchedule::create([
            'url' => 'https://missav.ai/en/spsb-038',
            'status' => 'pending',
        ]);

        $service = Mockery::mock(MissavService::class);
        $service->shouldReceive('item')
            ->once()
            ->with('https://missav.ai/en/spsb-038');
        $this->app->instance(MissavService::class, $service);

        $this->artisan('jav:missav:item')
            ->assertExitCode(0);

        $schedule->refresh();
        $this->assertSame('done', $schedule->status);
    }

    public function test_command_processes_given_url(): void
    {
        $service = Mockery::mock(MissavService::class);
        $service->shouldReceive('item')
            ->once()
            ->with('https://missav.ai/en/mxgs-1417');
        $this->app->instance(MissavService::class, $service);

        $this->artisan('jav:missav:item', ['url' => 'https://missav.ai/en/mxgs-1417'])
            ->assertExitCode(0);
    }

    public function test_command_processes_given_url_when_schedule_exists(): void
    {
        $schedule = MissavSchedule::create([
            'url' => 'https://missav.ai/en/spsb-038',
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $service = Mockery::mock(MissavService::class);
        $service->shouldReceive('item')
            ->once()
            ->with('https://missav.ai/en/spsb-038');
        $this->app->instance(MissavService::class, $service);

        $this->artisan('jav:missav:item', ['url' => 'https://missav.ai/en/spsb-038'])
            ->assertExitCode(0);

        $schedule->refresh();
        $this->assertSame('done', $schedule->status);
        $this->assertSame(1, $schedule->attempts);
    }
}
