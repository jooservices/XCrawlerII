<?php

namespace Modules\JAV\Tests\Unit\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use JOOservices\Client\Response\ResponseWrapper;
use Mockery;
use Modules\Core\Facades\Config;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Services\Clients\OneFourOneJavClient;
use Modules\JAV\Services\OneFourOneJav\ItemsAdapter;
use Modules\JAV\Services\OneFourOneJavService;
use Modules\JAV\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class OneFourOneJavServiceTest extends TestCase
{
    public function test_new(): void
    {
        Event::fake([ItemParsed::class, \Modules\JAV\Events\ItemsFetched::class]);

        $responseWrapper = $this->getMockResponse('141jav_new.html');

        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')->with('/new?page=1')->once()->andReturn($responseWrapper);

        $service = new OneFourOneJavService($client);
        $adapter = $service->new(1);

        $this->assertInstanceOf(ItemsAdapter::class, $adapter);
        $items = $adapter->items();
        $this->assertCount(10, $items->items);

        Event::assertDispatched(\Modules\JAV\Events\ItemsFetched::class, function (\Modules\JAV\Events\ItemsFetched $event) {
            return $event->source === '141jav'
                && $event->currentPage === 1
                && $event->items->items->count() === 10;
        });

        // ... (assertions)
    }

    // ... (other tests)

    public function test_popular(): void
    {
        Event::fake([ItemParsed::class, \Modules\JAV\Events\ItemsFetched::class]);

        $responseWrapper = $this->getMockResponse('141jav_popular.html');

        Config::shouldReceive('get')
            ->once()
            ->with('onefourone', 'popular_page', 1)
            ->andReturn(1); // Default to 1

        // In 141jav_popular.html, let's assume valid next page.
        // It's page 1, next is 2.
        Config::shouldReceive('set')
            ->once()
            ->with('onefourone', 'popular_page', 2);

        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')->with('/popular/?page=1')->once()->andReturn($responseWrapper);

        $service = new OneFourOneJavService($client);
        $adapter = $service->popular();

        $this->assertInstanceOf(ItemsAdapter::class, $adapter);
        $items = $adapter->items();

        // Verify items are parsed correctly
        $this->assertGreaterThan(0, $items->items->count());

        Event::assertDispatched(\Modules\JAV\Events\ItemsFetched::class, function (\Modules\JAV\Events\ItemsFetched $event) {
            return $event->source === '141jav'
                && $event->currentPage === 1
                && $event->items->items->count() > 0;
        });

        // ... (assertions)
    }

    public function test_popular_pagination_behavior(): void
    {
        // Test page 107 - should have next page
        $responseWrapper107 = $this->getMockResponse('141jav_popular_107.html');

        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')->with('/popular/?page=107')->once()->andReturn($responseWrapper107);

        $service = new OneFourOneJavService($client);
        $adapter = $service->popular(107);

        $this->assertEquals(107, $adapter->currentPage());
        $this->assertTrue($adapter->hasNextPage());
        $this->assertEquals(108, $adapter->nextPage());

        // Test page 108 - should be last page
        $responseWrapper108 = $this->getMockResponse('141jav_popular_108.html');

        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')->with('/popular/?page=108')->once()->andReturn($responseWrapper108);

        $service = new OneFourOneJavService($client);
        $adapter = $service->popular(108);

        $this->assertEquals(108, $adapter->currentPage());
        $this->assertFalse($adapter->hasNextPage());
        $this->assertEquals(1, $adapter->nextPage());
    }

    public function test_tags(): void
    {
        $client = app(OneFourOneJavClient::class);

        $service = new OneFourOneJavService($client);
        $this->assertInstanceOf(ResponseWrapper::class, $service->tags());
    }

    public static function itemDataProvider(): array
    {
        return [
            'ALOG026' => [
                'itemId' => 'ALOG026',
                'expected' => [
                    'id' => 'ALOG026',
                    'code' => 'ALOG-026',
                    'title' => 'ALOG026',
                    'url' => '/torrent/ALOG026',
                ],
            ],
            'TENN040' => [
                'itemId' => 'TENN040',
                'expected' => [
                    'id' => 'TENN040',
                    'code' => 'TENN-040',
                    'title' => 'TENN040',
                    'url' => '/torrent/TENN040',
                ],
            ],
            'TOTK016' => [
                'itemId' => 'TOTK016',
                'expected' => [
                    'id' => 'TOTK016',
                    'code' => 'TOTK-016',
                    'title' => 'TOTK016',
                    'url' => '/torrent/TOTK016',
                ],
            ],
            'KTKC052' => [
                'itemId' => 'KTKC052',
                'expected' => [
                    'id' => 'KTKC052',
                    'code' => 'KTKC-052',
                    'title' => 'KTKC052',
                    'url' => '/torrent/KTKC052',
                ],
            ],
            'FONE039' => [
                'itemId' => 'FONE039',
                'expected' => [
                    'id' => 'FONE039',
                    'code' => 'FONE-039',
                    'title' => 'FONE039',
                    'url' => '/torrent/FONE039',
                ],
            ],
            'FTAV016' => [
                'itemId' => 'FTAV016',
                'expected' => [
                    'id' => 'FTAV016',
                    'code' => 'FTAV-016',
                    'title' => 'FTAV016',
                    'url' => '/torrent/FTAV016',
                ],
            ],
            'START491' => [
                'itemId' => 'START491',
                'expected' => [
                    'id' => 'START491',
                    'code' => 'START-491',
                    'title' => 'START491',
                    'url' => '/torrent/START491',
                ],
            ],
            'SNOS100' => [
                'itemId' => 'SNOS100',
                'expected' => [
                    'id' => 'SNOS100',
                    'code' => 'SNOS-100',
                    'title' => 'SNOS100',
                    'url' => '/torrent/SNOS100',
                ],
            ],
        ];
    }

    #[DataProvider('itemDataProvider')]
    public function test_item(string $itemId, array $expected): void
    {
        Event::fake([ItemParsed::class]);

        $responseWrapper = $this->getMockResponse("141jav_item_{$itemId}.html");

        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')
            ->with("https://www.141jav.com/torrent/{$itemId}")
            ->once()
            ->andReturn($responseWrapper);

        $service = new OneFourOneJavService($client);
        $item = $service->item("https://www.141jav.com/torrent/{$itemId}");

        // Basic assertions
        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals($expected['id'], $item->id);
        $this->assertEquals($expected['code'], $item->code);
        $this->assertEquals($expected['title'], $item->title);
        $this->assertEquals($expected['url'], $item->url);

        // Image should exist
        $this->assertNotNull($item->image);
        $this->assertStringStartsWith('https://', $item->image);

        // Collection assertions
        $this->assertInstanceOf(Collection::class, $item->tags);
        $this->assertInstanceOf(Collection::class, $item->actresses);

        // Date should be parsed if present
        if ($item->date !== null) {
            $this->assertInstanceOf(\Carbon\Carbon::class, $item->date);
        }

        // Size should be valid if present
        if ($item->size !== null) {
            $this->assertIsFloat($item->size);
            $this->assertGreaterThan(0, $item->size);
        }

        // 141jav specific: download link should exist
        $this->assertNotNull($item->download);
        $this->assertStringContainsString('.torrent', $item->download);

        // Assert ItemParsed event was dispatched once
        Event::assertDispatched(ItemParsed::class, 1);

        // Assert event has correct source and item properties
        Event::assertDispatched(ItemParsed::class, function (ItemParsed $event) use ($item, $expected) {
            return $event->source === '141jav'
                && $event->item->id === $expected['id']
                && $event->item->code === $expected['code']
                && $event->item->title === $expected['title']
                && $event->item->url === $expected['url']
                && $event->item->image === $item->image
                && $event->item->download === $item->download;
        });
    }

    public function test_new_with_auto_mode()
    {
        Event::fake([ItemParsed::class, \Modules\JAV\Events\ItemsFetched::class]);
        Config::shouldReceive('get')
            ->once()
            ->with('onefourone', 'new_page', 1)
            ->andReturn(5);

        $responseWrapper = $this->getMockResponse('141jav_new.html');
        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/new?page=5')
            ->andReturn($responseWrapper);

        Config::shouldReceive('set')
            ->once()
            ->with('onefourone', 'new_page', 2);

        $service = new OneFourOneJavService($client);
        $items = $service->new();

        $this->assertInstanceOf(ItemsAdapter::class, $items);
        $this->assertEquals(2, $items->nextPage());

        Event::assertDispatched(\Modules\JAV\Events\ItemsFetched::class, function (\Modules\JAV\Events\ItemsFetched $event) {
            return $event->source === '141jav'
                && $event->currentPage === 1; // Fixture is page 1
        });
    }

    public function test_new_with_manual_page()
    {
        Event::fake([ItemParsed::class, \Modules\JAV\Events\ItemsFetched::class]);
        Config::shouldReceive('get')->never();
        Config::shouldReceive('set')->never();

        $responseWrapper = $this->getMockResponse('141jav_new.html');

        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/new?page=10')
            ->andReturn($responseWrapper);

        $service = new OneFourOneJavService($client);
        $items = $service->new(10);

        $this->assertInstanceOf(ItemsAdapter::class, $items);

        Event::assertDispatched(\Modules\JAV\Events\ItemsFetched::class, function (\Modules\JAV\Events\ItemsFetched $event) {
            return $event->source === '141jav'
                && $event->currentPage === 1;
        });
    }
}
