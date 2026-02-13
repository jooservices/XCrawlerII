<?php

namespace Modules\JAV\Tests\Unit\Listeners;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Listeners\JavSubscriber;
use Modules\JAV\Models\Jav;
use Modules\JAV\Services\JavManager;
use Modules\JAV\Tests\TestCase;

class JavSubscriberTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_implements_should_queue(): void
    {
        $listener = app(JavSubscriber::class);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $listener);
    }

    public function test_dispatch_event_stores_item_in_database(): void
    {
        $item = new Item(
            id: 'abp462',
            title: 'ABP462',
            url: '/torrent/abp462',
            image: 'https://example.com/image.jpg',
            date: Carbon::parse('2016-10-16'),
            code: 'ABP462',
            tags: collect(['Lingerie', 'Masturbation', 'Pantyhose', 'Solowork', 'Toy']),
            size: 1.2,
            description: 'Cum Lingerie Na 14 Nao Wakana',
            actresses: collect(['Nao Wakana']),
            download: '/torrent/abp462/download/91625328/onejav.com_abp462.torrent'
        );

        $event = new ItemParsed($item, 'unit-test');
        $subscriber = app(JavSubscriber::class);
        $subscriber->handle($event);

        $jav = Jav::where('code', 'ABP462')->where('source', 'unit-test')->first();

        $this->assertNotNull($jav);
        $this->assertEquals('ABP462', $jav->code);
        $this->assertEquals('unit-test', $jav->source);
        $this->assertEquals('ABP462', $jav->title);
        $this->assertEquals('/torrent/abp462', $jav->url);
        $this->assertEquals('https://example.com/image.jpg', $jav->image);
        $this->assertEquals('2016-10-16', $jav->date->format('Y-m-d'));
        $this->assertEquals(1.2, $jav->size);
        $this->assertEquals('Cum Lingerie Na 14 Nao Wakana', $jav->description);
        $this->assertEquals(['Lingerie', 'Masturbation', 'Pantyhose', 'Solowork', 'Toy'], $jav->tags->pluck('name')->sort()->values()->toArray());
        $this->assertEquals(['Nao Wakana'], $jav->actors->pluck('name')->sort()->values()->toArray());
        $this->assertEquals('/torrent/abp462/download/91625328/onejav.com_abp462.torrent', $jav->download);
    }

    public function test_dispatch_event_with_different_sources(): void
    {
        $item = new Item(
            id: 'ipz725',
            title: 'IPZ725',
            url: '/torrent/ipz725',
            image: 'https://example.com/ipz725.jpg',
            date: Carbon::parse('2016-10-16'),
            code: 'IPZ725',
            tags: collect(['Beautiful Girl', 'Kiss']),
            size: 1.1,
            description: 'Test description',
            actresses: collect(['Arisa Shindo']),
            download: '/torrent/ipz725/download/24954269/onejav.com_ipz725.torrent'
        );

        $subscriber = app(JavSubscriber::class);

        // Store from onejav source
        $subscriber->handle(new ItemParsed($item, 'unit-test'));

        $jav = Jav::where('code', 'IPZ725')->where('source', 'unit-test')->first();
        $this->assertNotNull($jav);
        $this->assertEquals('unit-test', $jav->source);
    }

    public function test_duplicate_dispatch_updates_not_duplicates(): void
    {
        $item = new Item(
            id: 'tek074',
            title: 'TEK074 - Original',
            url: '/torrent/tek074',
            image: 'https://example.com/tek074.jpg',
            date: Carbon::parse('2016-10-16'),
            code: 'TEK074',
            tags: collect([]),
            size: 1.4,
            description: null,
            actresses: collect(['Miharu Usa']),
            download: '/torrent/tek074/download/22345950/onejav.com_tek074.torrent'
        );

        $subscriber = app(JavSubscriber::class);

        // First dispatch
        $subscriber->handle(new ItemParsed($item, 'unit-test'));
        $first = Jav::where('code', 'TEK074')->where('source', 'unit-test')->first();
        $firstId = $first->id;

        // Second dispatch with updated title
        $updatedItem = new Item(
            id: 'tek074',
            title: 'TEK074 - Updated',
            url: '/torrent/tek074',
            image: 'https://example.com/tek074-v2.jpg',
            date: Carbon::parse('2016-10-16'),
            code: 'TEK074',
            tags: collect(['NewTag']),
            size: 1.5,
            description: 'Updated description',
            actresses: collect(['Miharu Usa', 'Another Actress']),
            download: '/torrent/tek074/download/22345950/onejav.com_tek074.torrent'
        );

        $subscriber->handle(new ItemParsed($updatedItem, 'unit-test'));

        // Should be 1 record, same ID, updated values
        $count = Jav::where('code', 'TEK074')->where('source', 'unit-test')->count();
        $this->assertEquals(1, $count);

        $updated = Jav::where('code', 'TEK074')->where('source', 'unit-test')->first();
        $this->assertEquals($firstId, $updated->id);
        $this->assertEquals('TEK074 - Updated', $updated->title);
        $this->assertEquals(1.5, $updated->size);
        $this->assertEquals(['NewTag'], $updated->tags->pluck('name')->sort()->values()->toArray());
        $this->assertEquals(['Another Actress', 'Miharu Usa'], $updated->actors->pluck('name')->sort()->values()->toArray());
    }

    public function test_dispatch_with_null_fields(): void
    {
        $item = new Item(
            id: 'nulltest',
            title: 'NULLTEST',
            url: '/torrent/nulltest',
            image: null,
            date: null,
            code: 'NULLTEST',
            tags: collect([]),
            size: null,
            description: null,
            actresses: collect([]),
            download: null
        );

        $subscriber = app(JavSubscriber::class);
        $subscriber->handle(new ItemParsed($item, 'unit-test'));

        $jav = Jav::where('code', 'NULLTEST')->where('source', 'unit-test')->first();
        $this->assertNotNull($jav);
        $this->assertNull($jav->image);
        $this->assertNull($jav->date);
        $this->assertNull($jav->size);
        $this->assertNull($jav->description);
        $this->assertNull($jav->download);
        $this->assertEmpty($jav->tags);
        $this->assertEmpty($jav->actors);
    }

    public function test_handle_throws_exception_on_manager_failure(): void
    {
        $item = new Item(
            id: 'failtest',
            title: 'FAILTEST',
            url: '/torrent/failtest',
            image: null,
            date: null,
            code: 'FAILTEST',
            tags: collect([]),
            size: null,
            description: null,
            actresses: collect([]),
            download: null
        );

        $event = new ItemParsed($item, 'unit-test');

        $manager = $this->createMock(JavManager::class);
        $manager->expects($this->once())
            ->method('store')
            ->willThrowException(new \Exception('Database error'));

        $subscriber = new JavSubscriber($manager);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $subscriber->handle($event);
    }
}
