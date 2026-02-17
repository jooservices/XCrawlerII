<?php

namespace Modules\JAV\Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Core\Models\Config;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Services\Clients\OnejavClient;
use Modules\JAV\Services\CrawlerPaginationStateService;
use Modules\JAV\Services\CrawlerResponseCacheService;
use Modules\JAV\Services\CrawlerStatusPolicyService;
use Modules\JAV\Services\OnejavService;
use Modules\JAV\Tests\TestCase;

class OnejavStateTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(OnejavClient $client): OnejavService
    {
        return new OnejavService(
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
            'group' => 'onejav',
            'key' => 'new_page',
            'value' => 16569,
        ]);

        // 2. Mock Client to return fixture with next page = 16570
        $responseWrapper = $this->getMockResponse('onejav_new_16569.html');
        $client = Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/new?page=16569')
            ->andReturn($responseWrapper);

        // 3. Resolve Service (Real Service, Mocked Client)
        $service = $this->makeService($client);

        // 4. Call new() in auto mode
        Event::fake([ItemParsed::class]);
        $service->new();

        // 5. Assert DB updated
        $this->assertDatabaseHas('configs', [
            'group' => 'onejav',
            'key' => 'new_page',
            'value' => 16570,
        ]);
    }

    public function test_it_does_not_update_config_in_manual_mode()
    {
        // 1. Seed Config
        Config::create([
            'group' => 'onejav',
            'key' => 'new_page',
            'value' => 10,
        ]);

        // 2. Mock Client
        $responseWrapper = $this->getMockResponse('onejav_new_16569.html');
        $client = Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/new?page=16569')
            ->andReturn($responseWrapper);

        // 3. Resolve Service
        $service = $this->makeService($client);

        // 4. Call new(16569) manually
        Event::fake([ItemParsed::class]);
        $service->new(16569);

        // 5. Assert DB NOT updated (remains 10)
        $this->assertDatabaseHas('configs', [
            'group' => 'onejav',
            'key' => 'new_page',
            'value' => 10,
        ]);
    }

    public function test_it_updates_config_after_fetching_popular_items_in_auto_mode()
    {
        // 1. Seed Config
        Config::create([
            'group' => 'onejav',
            'key' => 'popular_page',
            'value' => 1,
        ]);

        // 2. Mock Client (Assuming we have a popular fixture, if not use new fixture but mock URL)
        // I will use 'onejav_new_16569.html' but mock the URL /popular/?page=1
        // The fixture has next page logic, so it should extract something.
        // Wait, 'onejav_new_16569.html' has valid next page 16570.
        // If I use it, Config will update to 16570.
        // But popular usually starts at 1 and goes to 2.
        // I should use 'onejav_popular.html' if available.
        // Unit test used 'onejav_popular.html'. Let's check if it exists in File System?
        // I'll stick to 'onejav_new_16569.html' for reliability as I know it works, just testing the mechanism.

        $responseWrapper = $this->getMockResponse('onejav_new_16569.html');
        $client = Mockery::mock(OnejavClient::class);
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
            'group' => 'onejav',
            'key' => 'popular_page',
            'value' => 16570, // Because we used the 16569 fixture which points to 16570
        ]);
    }
}
