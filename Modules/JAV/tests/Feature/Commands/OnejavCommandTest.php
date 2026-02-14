<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\JAV\Jobs\OnejavJob;
use Modules\JAV\Tests\TestCase;

class OnejavCommandTest extends TestCase
{
    use RefreshDatabase;

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

        $this->artisan('jav:sync', ['provider' => 'onejav', '--type' => 'new'])
            ->assertExitCode(0);

        Queue::assertPushedOn('jav', OnejavJob::class, function ($job) {
            return $job->type === 'new';
        });
    }

    public function test_command_stores_data_via_event()
    {

        // 0. Mock Config to ensure page is 1
        \Modules\Core\Facades\Config::shouldReceive('get')
            ->with('onejav', 'new_page', 1)
            ->andReturn(1);
        \Modules\Core\Facades\Config::shouldReceive('set'); // Allow set

        // 1. Mock the client to return known data
        $client = \Mockery::mock(\Modules\JAV\Services\Clients\OnejavClient::class);
        $client->shouldReceive('get')
            ->with('/new?page=1') // Default start page is 1
            ->once()
            ->andReturn($this->getMockResponse('onejav_new_15670.html'));

        // Bind the mock to the container
        $this->app->instance(\Modules\JAV\Services\Clients\OnejavClient::class, $client);

        // 2. Ensure database is clean for this test
        \Modules\JAV\Models\Jav::where('source', 'onejav')->delete();

        // 4. Run the command. Queue is 'sync' so job runs immediately.
        $this->artisan('jav:sync', ['provider' => 'onejav', '--type' => 'new'])
            ->assertExitCode(0);

        // 5. Assert data is stored
        $this->assertDatabaseHas('jav', [
            'source' => 'onejav',
            'code' => 'ABP-462',
        ]);

        $this->assertEquals(6, \Modules\JAV\Models\Jav::where('source', 'onejav')->count());
    }

    public function test_command_rejects_invalid_type()
    {
        Queue::fake();

        $this->artisan('jav:sync', ['provider' => 'onejav', '--type' => 'invalid'])
            ->assertExitCode(2);

        Queue::assertNothingPushed();
    }

    public function test_command_dispatches_item_parsed_event()
    {
        \Illuminate\Support\Facades\Event::fake();

        // 0. Mock Config to ensure page is 1
        \Modules\Core\Facades\Config::shouldReceive('get')
            ->with('onejav', 'new_page', 1)
            ->andReturn(1);
        \Modules\Core\Facades\Config::shouldReceive('set');

        // Mock the client
        $client = \Mockery::mock(\Modules\JAV\Services\Clients\OnejavClient::class);
        $client->shouldReceive('get')
            ->with('/new?page=1')
            ->andReturn($this->getMockResponse('onejav_new_15670.html'));

        $this->app->instance(\Modules\JAV\Services\Clients\OnejavClient::class, $client);

        $this->artisan('jav:sync', ['provider' => 'onejav', '--type' => 'new'])
            ->assertExitCode(0);

        // Debug assertions
        if (\Illuminate\Support\Facades\Event::hasDispatched(\Modules\JAV\Events\OnejavJobFailed::class)) {
            $failedEvents = \Illuminate\Support\Facades\Event::dispatched(\Modules\JAV\Events\OnejavJobFailed::class);
            dump('OnejavJobFailed Dispatched:');
            foreach ($failedEvents as $event) {
                dump($event[0]->exception->getMessage());
            }
        }

        \Illuminate\Support\Facades\Event::assertDispatched(\Modules\JAV\Events\ItemsFetched::class);
    }
}
