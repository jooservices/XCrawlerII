<?php

namespace Modules\JAV\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Core\Models\Config;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Services\Clients\OneFourOneJavClient;
use Modules\JAV\Services\CrawlerPaginationStateService;
use Modules\JAV\Services\CrawlerResponseCacheService;
use Modules\JAV\Services\CrawlerStatusPolicyService;
use Modules\JAV\Services\OneFourOneJavService;
use Modules\JAV\Tests\TestCase;

class OneFourOneStateIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(OneFourOneJavClient $client): OneFourOneJavService
    {
        return new OneFourOneJavService(
            $client,
            app(CrawlerResponseCacheService::class),
            app(CrawlerPaginationStateService::class),
            app(CrawlerStatusPolicyService::class)
        );
    }

    public function test_it_updates_config_after_fetching_new_items_in_auto_mode()
    {
        // 1. Seed Config
        Config::create([
            'group' => 'onefourone',
            'key' => 'new_page',
            'value' => 1,
        ]);

        // 2. Mock Client to return fixture with next page = 2
        $responseWrapper = $this->getMockResponse('141jav_new.html');
        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/new?page=1')
            ->andReturn($responseWrapper);

        // 3. Resolve Service (Real Service, Mocked Client)
        $service = $this->makeService($client);

        // 4. Call new() in auto mode
        Event::fake([ItemParsed::class]);
        $service->new();

        // 5. Assert DB updated
        $this->assertDatabaseHas('configs', [
            'group' => 'onefourone',
            'key' => 'new_page',
            'value' => 2,
        ]);
    }

    public function test_it_does_not_update_config_in_manual_mode()
    {
        // 1. Seed Config
        Config::create([
            'group' => 'onefourone',
            'key' => 'new_page',
            'value' => 10,
        ]);

        // 2. Mock Client
        $responseWrapper = $this->getMockResponse('141jav_new.html');
        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/new?page=5')
            ->andReturn($responseWrapper);

        // 3. Resolve Service
        $service = $this->makeService($client);

        // 4. Call new(5) manually
        Event::fake([ItemParsed::class]);
        $service->new(5);

        // 5. Assert DB NOT updated (remains 10)
        $this->assertDatabaseHas('configs', [
            'group' => 'onefourone',
            'key' => 'new_page',
            'value' => 10,
        ]);
    }

    public function test_it_updates_config_after_fetching_popular_items_in_auto_mode()
    {
        // 1. Seed Config
        Config::create([
            'group' => 'onefourone',
            'key' => 'popular_page',
            'value' => 1,
        ]);

        // 2. Mock Client
        $responseWrapper = $this->getMockResponse('141jav_popular.html');
        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/popular/?page=1')
            ->andReturn($responseWrapper);

        // 3. Resolve Service
        $service = $this->makeService($client);

        // 4. Call popular() in auto mode
        Event::fake([ItemParsed::class]);
        $service->popular();

        // 5. Assert DB updated
        $this->assertDatabaseHas('configs', [
            'group' => 'onefourone',
            'key' => 'popular_page',
            'value' => 2,
        ]);
    }
}
