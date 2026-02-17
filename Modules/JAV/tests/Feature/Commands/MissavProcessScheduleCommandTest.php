<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Modules\JAV\Models\MissavSchedule;
use Modules\JAV\Tests\TestCase;
use Mockery;
use Modules\JAV\Services\MissavService;

class MissavProcessScheduleCommandTest extends TestCase
{
    public function test_command_dispatches_pending_items(): void
    {
        MissavSchedule::create([
            'url' => 'https://missav.ai/en/spsb-038',
            'status' => 'pending',
        ]);
        MissavSchedule::create([
            'url' => 'https://missav.ai/en/mxgs-1417',
            'status' => 'pending',
        ]);

        $this->artisan('jav:missav:process', ['--limit' => 2])
            ->assertExitCode(0);

        $this->assertSame(2, MissavSchedule::where('status', 'done')->count());
    }

    public function test_command_handles_empty_queue(): void
    {
        $this->artisan('jav:missav:process', ['--limit' => 1])
            ->assertExitCode(0);

        $this->assertSame(0, MissavSchedule::count());
    }

    public function test_command_marks_failed_on_exception(): void
    {
        $schedule = MissavSchedule::create([
            'url' => 'https://missav.ai/en/spsb-038',
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $service = Mockery::mock(MissavService::class);
        $service->shouldReceive('item')
            ->once()
            ->with('https://missav.ai/en/spsb-038')
            ->andThrow(new \RuntimeException('boom'));
        $this->app->instance(MissavService::class, $service);

        try {
            $this->artisan('jav:missav:process', ['--limit' => 1]);
            $this->fail('Expected exception was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('boom', $exception->getMessage());
        }

        $schedule->refresh();

        $this->assertSame('failed', $schedule->status);
        $this->assertSame(1, $schedule->attempts);
        $this->assertSame('boom', $schedule->last_error);
    }
}