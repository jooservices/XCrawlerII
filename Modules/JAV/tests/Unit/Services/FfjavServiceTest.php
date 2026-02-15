<?php

namespace Modules\JAV\Tests\Unit\Services;

use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Core\Facades\Config;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Services\Clients\FfjavClient;
use Modules\JAV\Services\Ffjav\ItemsAdapter;
use Modules\JAV\Services\FfjavService;
use Modules\JAV\Tests\TestCase;

class FfjavServiceTest extends TestCase
{
    public function test_new(): void
    {
        Event::fake([ItemParsed::class, \Modules\JAV\Events\ItemsFetched::class]);

        $responseWrapper = $this->getMockResponse('ffjav_new.html');
        $client = Mockery::mock(FfjavClient::class);
        $client->shouldReceive('get')
            ->once()
            ->withArgs(function (string $path): bool {
                return in_array($path, ['/javtorrent', '/javtorrent/page/1'], true);
            })
            ->andReturn($responseWrapper);

        Config::set('ffjav', 'new_page', '1');

        $service = new FfjavService($client);
        $adapter = $service->new();

        $this->assertInstanceOf(ItemsAdapter::class, $adapter);
        $items = $adapter->items();
        $this->assertCount(2, $items->items);
        $this->assertEquals('MKMP-707', $items->items->first()->code);
        $this->assertSame('2', (string) Config::get('ffjav', 'new_page', '0'));
    }

    public function test_popular_with_explicit_page_uses_path_pagination(): void
    {
        $responseWrapper = $this->getMockResponse('ffjav_popular.html');
        $client = Mockery::mock(FfjavClient::class);
        $client->shouldReceive('get')->with('/popular/page/2')->once()->andReturn($responseWrapper);

        $service = new FfjavService($client);
        $adapter = $service->popular(2);

        $this->assertEquals(1, $adapter->currentPage());
        $this->assertTrue($adapter->hasNextPage());
    }

    public function test_item_extracts_download_and_normalized_code(): void
    {
        $responseWrapper = $this->getMockResponse('ffjav_item_mkmp707.html');
        $client = Mockery::mock(FfjavClient::class);
        $client->shouldReceive('get')
            ->with('https://ffjav.com/torrent/mkmp-707-sample')
            ->once()
            ->andReturn($responseWrapper);

        $service = new FfjavService($client);
        $item = $service->item('https://ffjav.com/torrent/mkmp-707-sample');

        $this->assertEquals('MKMP-707', $item->code);
        $this->assertEquals('mkmp707', $item->id);
        $this->assertEquals('https://ffjav.com/download/2026/202602/ffjav.com_mkmp707.torrent', $item->download);
    }
}
