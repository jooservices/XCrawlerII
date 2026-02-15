<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Illuminate\Support\Facades\Queue;
use Modules\JAV\Jobs\OneFourOneJavJob;
use Modules\JAV\Tests\TestCase;

class OneFourOneJavCommandTest extends TestCase
{
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

        $this->artisan('jav:sync:content', ['provider' => '141jav', '--type' => ['new']])
            ->assertExitCode(0);

        Queue::assertPushedOn('jav', OneFourOneJavJob::class, function ($job) {
            return $job->type === 'new';
        });
    }

    public function test_command_stores_data_via_event()
    {
        \Modules\Core\Facades\Config::set('onefourone', 'new_page', '1');

        // 1. Mock the client
        $client = \Mockery::mock(\Modules\JAV\Services\Clients\OneFourOneJavClient::class);
        $client->shouldReceive('get')
            ->with('/new?page=1')
            ->once()
            ->andReturn($this->getMockResponse('141jav_new.html'));

        $this->app->instance(\Modules\JAV\Services\Clients\OneFourOneJavClient::class, $client);

        \Modules\JAV\Models\Jav::where('source', '141jav')->delete();

        // 3. Run command synchronously
        $this->artisan('jav:sync:content', ['provider' => '141jav', '--type' => ['new']])
            ->assertExitCode(0);

        $this->assertDatabaseHas('jav', [
            'source' => '141jav',
            'code' => 'ALOG-026',
        ]);

        $this->assertEquals(10, \Modules\JAV\Models\Jav::where('source', '141jav')->count());
    }

    public function test_command_rejects_invalid_type()
    {
        Queue::fake();

        $this->artisan('jav:sync:content', ['provider' => '141jav', '--type' => ['invalid']])
            ->assertExitCode(2);

        Queue::assertNothingPushed();
    }

    public function test_command_dispatches_item_parsed_event()
    {
        \Illuminate\Support\Facades\Event::fake();

        \Modules\Core\Facades\Config::set('onefourone', 'new_page', '1');

        $client = \Mockery::mock(\Modules\JAV\Services\Clients\OneFourOneJavClient::class);
        $client->shouldReceive('get')
            ->with('/new?page=1')
            ->andReturn($this->getMockResponse('141jav_new.html'));

        $this->app->instance(\Modules\JAV\Services\Clients\OneFourOneJavClient::class, $client);

        $this->artisan('jav:sync:content', ['provider' => '141jav', '--type' => ['new']])
            ->assertExitCode(0);

        \Illuminate\Support\Facades\Event::assertDispatched(\Modules\JAV\Events\ItemsFetched::class);
    }
}
