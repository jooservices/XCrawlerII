<?php

namespace Modules\JAV\Tests\Unit\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
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
    protected function setUp(): void
    {
        parent::setUp();
        \Modules\JAV\Models\Jav::disableSearchSyncing();
        \Modules\JAV\Models\Tag::disableSearchSyncing();
        \Modules\JAV\Models\Actor::disableSearchSyncing();
    }

    public function test_new(): void
    {
        Event::fake([
            ItemParsed::class,
            \Modules\JAV\Events\ItemsFetched::class,
            \Modules\JAV\Events\ProviderFetchStarted::class,
            \Modules\JAV\Events\ProviderFetchCompleted::class,
        ]);

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

        Event::assertDispatched(\Modules\JAV\Events\ProviderFetchStarted::class, function (\Modules\JAV\Events\ProviderFetchStarted $event): bool {
            return $event->source === '141jav'
                && $event->type === 'new'
                && $event->path === '/new?page=1'
                && $event->page === 1;
        });

        Event::assertDispatched(\Modules\JAV\Events\ProviderFetchCompleted::class, function (\Modules\JAV\Events\ProviderFetchCompleted $event): bool {
            return $event->source === '141jav'
                && $event->type === 'new'
                && $event->path === '/new?page=1'
                && $event->page === 1
                && $event->itemsCount === 10;
        });

        // ... (assertions)
    }

    // ... (other tests)

    public function test_popular(): void
    {
        Event::fake([ItemParsed::class, \Modules\JAV\Events\ItemsFetched::class]);

        $responseWrapper = $this->getMockResponse('141jav_popular.html');
        Config::set('onefourone', 'popular_page', '1');

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

        $this->assertSame('2', (string) Config::get('onefourone', 'popular_page', '0'));

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
        $this->assertInstanceOf(Collection::class, $service->tags());
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
        Config::set('onefourone', 'new_page', '5');

        $responseWrapper = $this->getMockResponse('141jav_new.html');
        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/new?page=5')
            ->andReturn($responseWrapper);

        $service = new OneFourOneJavService($client);
        $items = $service->new();

        $this->assertInstanceOf(ItemsAdapter::class, $items);
        $this->assertEquals(2, $items->nextPage());

        Event::assertDispatched(\Modules\JAV\Events\ItemsFetched::class, function (\Modules\JAV\Events\ItemsFetched $event) {
            return $event->source === '141jav'
                && $event->currentPage === 1; // Fixture is page 1
        });

        $this->assertSame('2', (string) Config::get('onefourone', 'new_page', '0'));
    }

    public function test_new_with_manual_page()
    {
        Event::fake([ItemParsed::class, \Modules\JAV\Events\ItemsFetched::class]);
        Config::set('onefourone', 'new_page', '9000');

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

        $this->assertSame('9000', (string) Config::get('onefourone', 'new_page', '0'));
    }

    public function test_bulk_tag_sync_creates_new_tags(): void
    {
        Event::fake([
            \Modules\JAV\Events\TagsSyncCompleted::class,
            \Modules\JAV\Events\TagsSyncFailed::class,
        ]);

        $responseWrapper = $this->getMockResponse('141jav_tags.html');

        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')->with('/tag')->once()->andReturn($responseWrapper);

        $service = new OneFourOneJavService($client);
        $tags = $service->tags();

        $this->assertGreaterThan(20, $tags->count());
        $this->assertSame($tags->count(), \Modules\JAV\Models\Tag::query()->count());
        $this->assertDatabaseHas('tags', ['name' => 'Anal']);
        $this->assertDatabaseHas('tags', ['name' => 'Mature Woman']);

        Event::assertDispatched(\Modules\JAV\Events\TagsSyncCompleted::class, function (\Modules\JAV\Events\TagsSyncCompleted $event) use ($tags): bool {
            return $event->source === '141jav'
                && $event->totalTags === $tags->count()
                && $event->insertedTags > 0;
        });
        Event::assertNotDispatched(\Modules\JAV\Events\TagsSyncFailed::class);
    }

    public function test_new_dispatches_provider_fetch_failed_on_exception(): void
    {
        Event::fake([\Modules\JAV\Events\ProviderFetchFailed::class]);

        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')->with('/new?page=1')->once()->andThrow(new \RuntimeException('network down'));

        $service = new OneFourOneJavService($client);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('network down');

        try {
            $service->new(1);
        } finally {
            Event::assertDispatched(\Modules\JAV\Events\ProviderFetchFailed::class, function (\Modules\JAV\Events\ProviderFetchFailed $event): bool {
                return $event->source === '141jav'
                    && $event->type === 'new'
                    && $event->path === '/new?page=1'
                    && $event->page === 1
                    && str_contains($event->error, 'network down');
            });
        }
    }

    public function test_tags_dispatches_tags_sync_failed_on_exception(): void
    {
        Event::fake([\Modules\JAV\Events\TagsSyncFailed::class]);

        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')->with('/tag')->once()->andThrow(new \RuntimeException('tag unavailable'));

        $service = new OneFourOneJavService($client);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('tag unavailable');

        try {
            $service->tags();
        } finally {
            Event::assertDispatched(\Modules\JAV\Events\TagsSyncFailed::class, function (\Modules\JAV\Events\TagsSyncFailed $event): bool {
                return $event->source === '141jav'
                    && str_contains($event->error, 'tag unavailable');
            });
        }
    }

    public function test_bulk_tag_sync_does_not_duplicate_existing_tags(): void
    {
        \Modules\JAV\Models\Tag::query()->create(['name' => 'Anal']);

        $responseWrapper = $this->getMockResponse('141jav_tags.html');
        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')->with('/tag')->once()->andReturn($responseWrapper);

        $service = new OneFourOneJavService($client);
        $tags = $service->tags();

        $this->assertSame(1, \Modules\JAV\Models\Tag::query()->where('name', 'Anal')->count());
        $this->assertSame($tags->count(), \Modules\JAV\Models\Tag::query()->count());
    }

    public function test_bulk_tag_sync_filters_empty_and_duplicate_names(): void
    {
        $responseWrapper = $this->getMockResponse('141jav_tags.html');
        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')->with('/tag')->once()->andReturn($responseWrapper);

        $service = new OneFourOneJavService($client);
        $tags = $service->tags();

        $this->assertSame($tags->count(), $tags->unique()->count());
        $this->assertSame($tags->count(), \Modules\JAV\Models\Tag::query()->count());
    }

    public function test_bulk_tag_sync_returns_empty_when_no_tag_nodes(): void
    {
        $responseWrapper = $this->getMockResponse('141jav_new.html');
        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')->with('/tag')->once()->andReturn($responseWrapper);

        $service = new OneFourOneJavService($client);
        $tags = $service->tags();

        $this->assertCount(0, $tags);
        $this->assertSame(0, \Modules\JAV\Models\Tag::query()->count());
    }
}
