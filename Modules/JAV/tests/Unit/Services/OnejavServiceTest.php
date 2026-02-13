<?php

namespace Modules\JAV\Tests\Unit\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Modules\Core\Facades\Config;
use JOOservices\Client\Response\Response;
use JOOservices\Client\Response\ResponseWrapper;
use Mockery;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Services\Clients\OnejavClient;
use Modules\JAV\Services\Onejav\ItemsAdapter;
use Modules\JAV\Services\OnejavService;
use Modules\JAV\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class OnejavServiceTest extends TestCase
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
        Event::fake([ItemParsed::class, \Modules\JAV\Events\ItemsFetched::class]);

        $responseWrapper = $this->getMockResponse('onejav_new_15670.html');

        $client = Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')->with('/new?page=16570')->once()->andReturn($responseWrapper);

        $service = new OnejavService($client);
        $adapter = $service->new(16570);

        $this->assertInstanceOf(ItemsAdapter::class, $adapter);
        $items = $adapter->items();
        $this->assertCount(6, $items->items);

        $expectedItems = [
            [
                'url' => '/torrent/abp462',
                'title' => 'ABP462',
                'id' => 'abp462',
                'size' => 1.2,
                'date' => '2016-10-16',
                'actresses' => ['Nao Wakana'],
                'tags' => ['Lingerie', 'Masturbation', 'Pantyhose', 'Solowork', 'Toy'],
                'description' => 'Cum Lingerie Na 14 Nao Wakana',
                'download' => '/torrent/abp462/download/91625328/onejav.com_abp462.torrent',
            ],
            [
                'url' => '/torrent/ipz725',
                'title' => 'IPZ725',
                'id' => 'ipz725',
                'size' => 1.1,
                'date' => '2016-10-16',
                'actresses' => ['Arisa Shindo'],
                'tags' => ['Beautiful Girl', 'Digital Mosaic', 'Kiss', 'Solowork', 'Subjectivity'],
                'description' => 'Sweet Or Hard? Which Is Like? Sweet And Intense Rich Kiss And SEX Intense Kiss ... Sweet Kiss ... You Which Is Excited? Shindo Arisa',
                'download' => '/torrent/ipz725/download/24954269/onejav.com_ipz725.torrent',
            ],
            [
                'url' => '/torrent/abp459',
                'title' => 'ABP459',
                'id' => 'abp459',
                'size' => 1.2,
                'date' => '2016-10-16',
                'actresses' => ['Kaede Fuyutsuki'],
                'tags' => ['3P, 4P', 'Couple', 'Cuckold', 'Sister', 'Solowork'],
                'description' => 'Her Older Sister Is, Temptation Spear Was Shy Daughter. Winter Months Maple',
                'download' => '/torrent/abp459/download/48053912/onejav.com_abp459.torrent',
            ],
            [
                'url' => '/torrent/tek074',
                'title' => 'TEK074',
                'id' => 'tek074',
                'size' => 1.4,
                'date' => '2016-10-16',
                'actresses' => ['Miharu Usa'],
                'tags' => [],
                'description' => null,
                'download' => '/torrent/tek074/download/22345950/onejav.com_tek074.torrent',
            ],
            [
                'url' => '/torrent/tek075',
                'title' => 'TEK075',
                'id' => 'tek075',
                'size' => 0.80380859375, // 823.1 MB
                'date' => '2016-10-16',
                'actresses' => ['岡田真由香'],
                'tags' => [],
                'description' => null,
                'download' => '/torrent/tek075/download/48318078/onejav.com_tek075.torrent',
            ],
            [
                'url' => '/torrent/sga049',
                'title' => 'SGA049',
                'id' => 'sga049',
                'size' => 1.2,
                'date' => '2016-10-16',
                'actresses' => ['夏原あかり'],
                'tags' => ['3P, 4P', 'Debut Production', 'Electric Massager', 'Married Woman', 'Slender', 'Solowork', 'Squirting'],
                'description' => 'If Re Juice Nuo Married Akari Natsuhara 29-year-old AV Debut Idi Too Spray Tide Complete Recording Of The Moment When Love Was Overflowing As The Fiddle Is Blown Out From The Genitals! !',
                'download' => '/torrent/sga049/download/71849447/onejav.com_sga049.torrent',
            ],
        ];

        foreach ($items->items as $index => $item) {
            $expected = $expectedItems[$index];
            $this->assertEquals($expected['url'], $item->url);
            $this->assertEquals($expected['title'], $item->title);
            $this->assertEquals($expected['id'], $item->id);
            $this->assertEquals($expected['size'], $item->size);
            $this->assertEquals($expected['date'], $item->date->format('Y-m-d'));
            $this->assertEquals($expected['actresses'], $item->actresses->toArray());
            $this->assertEquals($expected['tags'], $item->tags->toArray());
            $this->assertEquals($expected['description'], $item->description);
            $this->assertEquals($expected['download'], $item->download);
        }

        // Assert ItemParsed event was dispatched 6 times (once per item)
        Event::assertDispatched(ItemParsed::class, 6);

        // Assert each event has correct source and item properties
        Event::assertDispatched(ItemParsed::class, function (ItemParsed $event) use ($expectedItems) {
            // Verify source is 'onejav'
            if ($event->source !== 'onejav') {
                return false;
            }

            // Verify the item matches one of the expected items
            foreach ($expectedItems as $expected) {
                if ($event->item->id === $expected['id']) {
                    return $event->item->url === $expected['url']
                        && $event->item->title === $expected['title']
                        && $event->item->size === $expected['size']
                        && $event->item->download === $expected['download'];
                }
            }

            return false;
        });

        Event::assertDispatched(\Modules\JAV\Events\ItemsFetched::class, function (\Modules\JAV\Events\ItemsFetched $event) {
            return $event->source === 'onejav'
                && $event->currentPage === 16570
                && $event->items->items->count() === 6;
        });
    }

    public function test_new_last_page(): void
    {
        $responseWrapper = $this->getMockResponse('onejav_new_15670.html');

        $client = Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')->with('/new?page=16570')->once()->andReturn($responseWrapper);

        $service = new OnejavService($client);
        $adapter = $service->new(16570);

        $this->assertEquals(16570, $adapter->currentPage());
        $this->assertFalse($adapter->hasNextPage());
        $this->assertEquals(1, $adapter->nextPage());
    }

    public function test_new_pagination_behavior(): void
    {
        // Test page 16569 - should have next page
        $responseWrapper16569 = $this->getMockResponse('onejav_new_16569.html');

        $client = Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')->with('/new?page=16569')->once()->andReturn($responseWrapper16569);

        $service = new OnejavService($client);
        $adapter = $service->new(16569);

        $this->assertEquals(16569, $adapter->currentPage());
        $this->assertTrue($adapter->hasNextPage());
        $this->assertEquals(16570, $adapter->nextPage());

        // Test page 16570 - should be last page
        $responseWrapper16570 = $this->getMockResponse('onejav_new_16570.html');

        $client = Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')->with('/new?page=16570')->once()->andReturn($responseWrapper16570);

        $service = new OnejavService($client);
        $adapter = $service->new(16570);

        $this->assertEquals(16570, $adapter->currentPage());
        $this->assertFalse($adapter->hasNextPage());
        $this->assertEquals(1, $adapter->nextPage());
    }

    public function test_popular(): void
    {
        Event::fake([ItemParsed::class, \Modules\JAV\Events\ItemsFetched::class]);

        $responseWrapper = $this->getMockResponse('onejav_popular.html');

        // Mock Config because popular() is called without args (auto mode)
        Config::shouldReceive('get')
            ->once()
            ->with('onejav', 'popular_page', 1)
            ->andReturn(1); // Default to 1

        // Mock Config set because next page will be extracted
        // In onejav_popular.html, let's assume valid next page.
        // It's page 1, next is 2.
        Config::shouldReceive('set')
            ->once()
            ->with('onejav', 'popular_page', 2);

        $client = Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')->with('/popular/?page=1')->once()->andReturn($responseWrapper);

        $service = new OnejavService($client);
        $adapter = $service->popular();

        $this->assertInstanceOf(ItemsAdapter::class, $adapter);
        $items = $adapter->items();

        // Verify items are parsed correctly
        $this->assertGreaterThan(0, $items->items->count());

        // Verify each item has required fields
        foreach ($items->items as $item) {
            $this->assertNotNull($item->url);
            $this->assertNotNull($item->title);
            $this->assertNotNull($item->id);
        }

        // Assert ItemParsed event was dispatched for each item
        Event::assertDispatched(ItemParsed::class, $items->items->count());

        // Assert all events have correct source
        Event::assertDispatched(ItemParsed::class, function (ItemParsed $event) {
            return $event->source === 'onejav';
        });

        Event::assertDispatched(\Modules\JAV\Events\ItemsFetched::class, function (\Modules\JAV\Events\ItemsFetched $event) {
            return $event->source === 'onejav'
                && $event->currentPage === 1
                && $event->items->items->count() > 0;
        });
    }

    public function test_popular_pagination_behavior(): void
    {
        // Test page 4 - should have next page
        $responseWrapper4 = $this->getMockResponse('onejav_popular_4.html');

        $client = Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')->with('/popular/?page=4')->once()->andReturn($responseWrapper4);

        $service = new OnejavService($client);
        $adapter = $service->popular(4);

        $this->assertEquals(4, $adapter->currentPage());
        $this->assertTrue($adapter->hasNextPage());
        $this->assertEquals(5, $adapter->nextPage());

        // Test page 5 - should be last page
        $responseWrapper5 = $this->getMockResponse('onejav_popular_5.html');

        $client = Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')->with('/popular/?page=5')->once()->andReturn($responseWrapper5);

        $service = new OnejavService($client);
        $adapter = $service->popular(5);

        $this->assertEquals(5, $adapter->currentPage());
        $this->assertFalse($adapter->hasNextPage());
        $this->assertEquals(1, $adapter->nextPage());
    }

    public function test_tags(): void
    {
        $client = app(OnejavClient::class);

        $service = new OnejavService($client);
        $this->assertInstanceOf(ResponseWrapper::class, $service->tags());
    }

    public static function itemDataProvider(): array
    {
        return [
            'cherd102' => [
                'itemId' => 'cherd102',
                'expected' => [
                    'code' => 'CHERD102',
                    'id' => 'cherd102',
                    'title' => 'CHERD102',
                    'url' => '/torrent/cherd102',
                ],
            ],
            'jrze286' => [
                'itemId' => 'jrze286',
                'expected' => [
                    'code' => 'JRZE286',
                    'id' => 'jrze286',
                    'title' => 'JRZE286',
                    'url' => '/torrent/jrze286',
                ],
            ],
            'fc2ppv4846863' => [
                'itemId' => 'fc2ppv4846863',
                'expected' => [
                    'code' => 'FC2PPV4846863',
                    'id' => 'fc2ppv4846863',
                    'title' => 'FC2PPV4846863',
                    'url' => '/torrent/fc2ppv4846863',
                ],
            ],
            'fc2ppv4846667' => [
                'itemId' => 'fc2ppv4846667',
                'expected' => [
                    'code' => 'FC2PPV4846667',
                    'id' => 'fc2ppv4846667',
                    'title' => 'FC2PPV4846667',
                    'url' => '/torrent/fc2ppv4846667',
                ],
            ],
            'fc2ppv4846656' => [
                'itemId' => 'fc2ppv4846656',
                'expected' => [
                    'code' => 'FC2PPV4846656',
                    'id' => 'fc2ppv4846656',
                    'title' => 'FC2PPV4846656',
                    'url' => '/torrent/fc2ppv4846656',
                ],
            ],
            'fc2ppv4846700' => [
                'itemId' => 'fc2ppv4846700',
                'expected' => [
                    'code' => 'FC2PPV4846700',
                    'id' => 'fc2ppv4846700',
                    'title' => 'FC2PPV4846700',
                    'url' => '/torrent/fc2ppv4846700',
                ],
            ],
            'mogi151' => [
                'itemId' => 'mogi151',
                'expected' => [
                    'code' => 'MOGI151',
                    'id' => 'mogi151',
                    'title' => 'MOGI151',
                    'url' => '/torrent/mogi151',
                ],
            ],
            'jur061' => [
                'itemId' => 'jur061',
                'expected' => [
                    'code' => 'JUR061',
                    'id' => 'jur061',
                    'title' => 'JUR061',
                    'url' => '/torrent/jur061',
                ],
            ],
        ];
    }

    #[DataProvider('itemDataProvider')]
    public function testItem(string $itemId, array $expected): void
    {
        Event::fake([ItemParsed::class]);

        $responseWrapper = $this->getMockResponse("onejav_item_{$itemId}.html");

        $client = Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')
            ->with("https://onejav.com/torrent/{$itemId}")
            ->once()
            ->andReturn($responseWrapper);

        $service = new OnejavService($client);
        $item = $service->item("https://onejav.com/torrent/{$itemId}");

        // Basic assertions
        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals($expected['code'], $item->code);
        $this->assertEquals($expected['id'], $item->id);
        $this->assertEquals($expected['title'], $item->title);
        $this->assertEquals($expected['url'], $item->url);

        // Image should exist
        $this->assertNotNull($item->image);
        $this->assertStringStartsWith('https://', $item->image);

        // Size assertion if provided
        if (isset($expected['size'])) {
            $this->assertEquals($expected['size'], $item->size);
        }

        // Collection assertions
        $this->assertInstanceOf(Collection::class, $item->tags);
        $this->assertInstanceOf(Collection::class, $item->actresses);

        // Date should be parsed if present
        if ($item->date !== null) {
            $this->assertInstanceOf(\Carbon\Carbon::class, $item->date);
        }

        // Assert ItemParsed event was dispatched once
        Event::assertDispatched(ItemParsed::class, 1);

        // Assert event has correct source and item properties
        Event::assertDispatched(ItemParsed::class, function (ItemParsed $event) use ($item, $expected) {
            return $event->source === 'onejav'
                && $event->item->id === $expected['id']
                && $event->item->code === $expected['code']
                && $event->item->title === $expected['title']
                && $event->item->url === $expected['url']
                && $event->item->image === $item->image;
        });
    }

    public function test_new_with_auto_mode()
    {
        Event::fake([ItemParsed::class, \Modules\JAV\Events\ItemsFetched::class]);
        Config::shouldReceive('get')
            ->once()
            ->with('onejav', 'new_page', 1)
            ->andReturn(16569);

        $responseWrapper = $this->getMockResponse('onejav_new_16569.html');

        $client = Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/new?page=16569')
            ->andReturn($responseWrapper);

        Config::shouldReceive('set')
            ->once()
            ->with('onejav', 'new_page', 16570);

        $service = new OnejavService($client);
        $items = $service->new();

        $this->assertInstanceOf(ItemsAdapter::class, $items);
        $this->assertEquals(16570, $items->nextPage());

        Event::assertDispatched(\Modules\JAV\Events\ItemsFetched::class, function (\Modules\JAV\Events\ItemsFetched $event) {
            return $event->source === 'onejav'
                && $event->currentPage === 16569;
        });
    }

    public function test_new_with_manual_page()
    {
        Event::fake([ItemParsed::class, \Modules\JAV\Events\ItemsFetched::class]);
        Config::shouldReceive('get')->never();
        Config::shouldReceive('set')->never();

        $responseWrapper = $this->getMockResponse('onejav_new_16569.html');

        $client = Mockery::mock(OnejavClient::class);
        $client->shouldReceive('get')
            ->once()
            ->with('/new?page=16569') // Using 16569 for manual test too
            ->andReturn($responseWrapper);

        $service = new OnejavService($client);
        $items = $service->new(16569);

        $this->assertInstanceOf(ItemsAdapter::class, $items);

        Event::assertDispatched(\Modules\JAV\Events\ItemsFetched::class, function (\Modules\JAV\Events\ItemsFetched $event) {
            return $event->source === 'onejav'
                && $event->currentPage === 16569;
        });
    }
}
