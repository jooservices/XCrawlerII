<?php

namespace Modules\JAV\Tests\Unit\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use JOOservices\Client\Response\Response;
use JOOservices\Client\Response\ResponseWrapper;
use Mockery;
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
        Event::fake([ItemParsed::class]);

        $responseWrapper = $this->getMockResponse('141jav_new.html');

        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')->with('/new?page=1')->once()->andReturn($responseWrapper);

        $service = new OneFourOneJavService($client);
        $adapter = $service->new(1);

        $this->assertInstanceOf(ItemsAdapter::class, $adapter);
        $items = $adapter->items();

        // Count items in fixture
        $this->assertCount(10, $items->items);

        $expectedItems = [
            [
                'url' => '/torrent/AED254',
                'title' => 'AED254',
                'id' => 'AED254',
                'size' => 4.8,
                'date' => '2026-02-12',
                'actresses' => ['Ishino Shouko'],
                'tags' => ['Mature Woman', 'Massage', 'Married Woman', 'Big Tits', 'Solowork'],
                'description' => "Mature Woman's Play: Midori Okae, A 50-year-old Mother Addicted To A Business Trip For Women",
                'download' => '/download/AED254.torrent',
            ],
            [
                'url' => '/torrent/ALOG026',
                'title' => 'ALOG026',
                'id' => 'ALOG026',
                'size' => 4.3,
                'date' => '2026-02-12',
                'actresses' => [],
                'tags' => ['Urination', 'Footjob', '3P', '4P', 'Creampie', 'Cosplay'],
                'description' => "I Seduced A Cafe Girl Who Had A Boyfriend And Had Sex With Her After Work.",
                'download' => '/download/ALOG026.torrent',
            ],
            [
                'url' => '/torrent/BDH008',
                'title' => 'BDH008',
                'id' => 'BDH008',
                'size' => 5.2,
                'date' => '2026-02-12',
                'actresses' => ['Akane Ayaka'],
                'tags' => ['Huge Butt', 'BBW', 'School Swimsuit', 'Cum', 'Facials', 'Big Tits', 'Uniform', 'Solowork', 'Creampie'],
                'description' => "Plump Female Pig Meat Girl, The Eighth Meat Girl, Ayaka Mineno",
                'download' => '/download/BDH008.torrent',
            ],
            [
                'url' => '/torrent/BMW351',
                'title' => 'BMW351',
                'id' => 'BMW351',
                'size' => 10.1,
                'date' => '2026-02-12',
                'actresses' => ['Arai Rima', 'Misono Waka', 'Kawana Minori', 'Himekawa Yuuna', 'Kano Hana', 'Sasaki Aki', 'Kamihata Ichika', 'Onoue Wakaba', 'Asakura Kotomi', 'Asakura Yuu'],
                'tags' => ['4HR+', 'Subjectivity', 'Slut', 'Beautiful Girl', 'Big Tits', 'Blow'],
                'description' => "A Completely Subjective, Immersive Experience! A Face-focused Blowjob That Lets You Feel The Overwhelming Licking And Sucking Technique From Zero Distance.",
                'download' => '/download/BMW351.torrent',
            ],
            [
                'url' => '/torrent/CHERD102',
                'title' => 'CHERD102',
                'id' => 'CHERD102',
                'size' => 4.4,
                'date' => '2026-02-12',
                'actresses' => ['Sawaguchi Shino'],
                'tags' => ['Virgin Man', 'Mature Woman', 'Documentary', 'Married Woman', 'Big Tits', 'Solowork'],
                'description' => "\"Would You Mind If Your First Time Was Raw With An Older Woman?\" A Virgin Loses His Virginity To A Married Mature Woman In The Best Possible Way - Shino Sawaguchi",
                'download' => '/download/CHERD102.torrent',
            ],
            [
                'url' => '/torrent/DAZD277',
                'title' => 'DAZD277',
                'id' => 'DAZD277',
                'size' => 9.6,
                'date' => '2026-02-12',
                'actresses' => ['Matsui Hinako', 'Aizawa Miyu', 'Itsukaichi Mei', 'Yuuki Ai', 'Kuramoto Sumire', 'Kagari Mai', 'Mizuno Natsuki', 'Ishihara Nozomi', 'Saitou Amiri', 'Amamiya Kotone'],
                'tags' => ['Squirting', 'Cowgirl', 'Nasty', 'Hardcore', 'Big Tits', 'Best', 'Omnibus'],
                'description' => "MA×KODOM ~ After The Great Ascension, The Squirting Cumshot ~ 13,800 Seconds, 59 Girls, Wet BEST",
                'download' => '/download/DAZD277.torrent',
            ],
            [
                'url' => '/torrent/GMA090',
                'title' => 'GMA090',
                'id' => 'GMA090',
                'size' => 4.9,
                'date' => '2026-02-12',
                'actresses' => ['Tada Yuka'],
                'tags' => ['Mature Woman', 'Abuse', 'Shibari', 'Solowork', 'SM'],
                'description' => "Bondage Training Wife: The Lewd Behavior Of A Masochistic Wife Obsessed With The Pleasures Of Rope. The Secret Hidden In The Memory Of Her Husband Lost In A Traffic Accident. Yuka Tada",
                'download' => '/download/GMA090.torrent',
            ],
            [
                'url' => '/torrent/GOJI084',
                'title' => 'GOJI084',
                'id' => 'GOJI084',
                'size' => 9.3,
                'date' => '2026-02-12',
                'actresses' => [],
                'tags' => ['Huge Butt', 'BBW', 'Mother', 'Mature Woman', 'Big Tits', 'Best', 'Omnibus', 'Creampie'],
                'description' => "A 50-year-old Mother Who Even Embraces Her Own Son",
                'download' => null,
            ],
            [
                'url' => '/torrent/HBAD727',
                'title' => 'HBAD727',
                'id' => 'HBAD727',
                'size' => 5.0,
                'date' => '2026-02-12',
                'actresses' => ['Minami Amane'],
                'tags' => ['Solowork'],
                'description' => "Big Tits, Big Ass, Plump Body, Carnal Desire, Dried Fish Pussy Sister Shows Off Erotic Appearance And Seduces You Minami Amane",
                'download' => '/download/HBAD727.torrent',
            ],
            [
                'url' => '/torrent/HONB467',
                'title' => 'HONB467',
                'id' => 'HONB467',
                'size' => 2.8,
                'date' => '2026-02-12',
                'actresses' => [],
                'tags' => ['POV', 'Gal', 'Creampie', 'Blow'],
                'description' => "Showa Safe 3",
                'download' => '/download/HONB467.torrent',
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
            if (isset($expected['download'])) {
                $this->assertEquals($expected['download'], $item->download);
            }
        }

        // Assert ItemParsed event was dispatched 10 times (once per item)
        Event::assertDispatched(ItemParsed::class, 10);

        // Assert each event has correct source and item properties
        Event::assertDispatched(ItemParsed::class, function (ItemParsed $event) use ($expectedItems) {
            // Verify source is '141jav'
            if ($event->source !== '141jav') {
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
    }

    public function test_popular(): void
    {
        Event::fake([ItemParsed::class]);

        $responseWrapper = $this->getMockResponse('141jav_popular.html');

        $client = Mockery::mock(OneFourOneJavClient::class);
        $client->shouldReceive('get')->with('/popular/?page=1')->once()->andReturn($responseWrapper);

        $service = new OneFourOneJavService($client);
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
            return $event->source === '141jav';
        });
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
                    'code' => 'ALOG026',
                    'title' => 'ALOG026',
                    'url' => '/torrent/ALOG026',
                ],
            ],
            'TENN040' => [
                'itemId' => 'TENN040',
                'expected' => [
                    'id' => 'TENN040',
                    'code' => 'TENN040',
                    'title' => 'TENN040',
                    'url' => '/torrent/TENN040',
                ],
            ],
            'TOTK016' => [
                'itemId' => 'TOTK016',
                'expected' => [
                    'id' => 'TOTK016',
                    'code' => 'TOTK016',
                    'title' => 'TOTK016',
                    'url' => '/torrent/TOTK016',
                ],
            ],
            'KTKC052' => [
                'itemId' => 'KTKC052',
                'expected' => [
                    'id' => 'KTKC052',
                    'code' => 'KTKC052',
                    'title' => 'KTKC052',
                    'url' => '/torrent/KTKC052',
                ],
            ],
            'FONE039' => [
                'itemId' => 'FONE039',
                'expected' => [
                    'id' => 'FONE039',
                    'code' => 'FONE039',
                    'title' => 'FONE039',
                    'url' => '/torrent/FONE039',
                ],
            ],
            'FTAV016' => [
                'itemId' => 'FTAV016',
                'expected' => [
                    'id' => 'FTAV016',
                    'code' => 'FTAV016',
                    'title' => 'FTAV016',
                    'url' => '/torrent/FTAV016',
                ],
            ],
            'START491' => [
                'itemId' => 'START491',
                'expected' => [
                    'id' => 'START491',
                    'code' => 'START491',
                    'title' => 'START491',
                    'url' => '/torrent/START491',
                ],
            ],
            'SNOS100' => [
                'itemId' => 'SNOS100',
                'expected' => [
                    'id' => 'SNOS100',
                    'code' => 'SNOS100',
                    'title' => 'SNOS100',
                    'url' => '/torrent/SNOS100',
                ],
            ],
        ];
    }

    #[DataProvider('itemDataProvider')]
    public function testItem(string $itemId, array $expected): void
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
}
