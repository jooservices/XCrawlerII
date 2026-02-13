<?php

namespace Modules\JAV\Tests\Feature\Services;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Listeners\JavSubscriber;
use Modules\JAV\Models\Jav;
use Modules\JAV\Services\JavManager;
use Modules\JAV\Tests\TestCase;

/**
 * Feature test that writes REAL data to the database using JavManager.
 * Covers both direct Manager usage and Subscriber usage.
 */
class JavManagerTest extends TestCase
{
    use RefreshDatabase;

    private JavManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new JavManager;
    }

    public function test_manager_stores_real_data(): void
    {
        $item = new Item(
            id: 'abp462',
            title: 'ABP462',
            url: '/torrent/abp462',
            image: 'https://example.com/abp462.jpg',
            date: Carbon::parse('2016-10-16'),
            code: 'ABP462',
            tags: collect(['Lingerie', 'Masturbation', 'Pantyhose', 'Solowork', 'Toy']),
            size: 1.2,
            description: 'Cum Lingerie Na 14 Nao Wakana',
            actresses: collect(['Nao Wakana']),
            download: '/torrent/abp462/download/91625328/onejav.com_abp462.torrent'
        );

        $jav = $this->manager->store($item, 'feature-test');

        $this->assertInstanceOf(Jav::class, $jav);
        $this->assertNotNull($jav->id);
        $this->assertEquals('ABP462', $jav->code);
        $this->assertEquals('feature-test', $jav->source);
        $this->assertEquals(1.2, $jav->size);
        $this->assertEquals(['Lingerie', 'Masturbation', 'Pantyhose', 'Solowork', 'Toy'], $jav->tags->pluck('name')->sort()->values()->toArray());
        $this->assertEquals(['Nao Wakana'], $jav->actors->pluck('name')->sort()->values()->toArray());
    }

    public function test_subscriber_stores_real_data(): void
    {
        $item = new Item(
            id: 'ipz725',
            title: 'IPZ725',
            url: '/torrent/ipz725',
            image: 'https://example.com/ipz725.jpg',
            date: Carbon::parse('2016-10-16'),
            code: 'IPZ725',
            tags: collect(['Beautiful Girl', 'Digital Mosaic', 'Kiss', 'Solowork', 'Subjectivity']),
            size: 1.1,
            description: 'Sweet Or Hard?',
            actresses: collect(['Arisa Shindo']),
            download: '/torrent/ipz725/download/24954269/onejav.com_ipz725.torrent'
        );

        $subscriber = app(JavSubscriber::class);
        $subscriber->handle(new ItemParsed($item, 'feature-test'));

        $jav = Jav::where('code', 'IPZ725')->where('source', 'feature-test')->first();

        $this->assertNotNull($jav);
        $this->assertEquals('IPZ725', $jav->code);
        $this->assertEquals('feature-test', $jav->source);
        $this->assertEquals(1.1, $jav->size);
        $this->assertEquals(['Beautiful Girl', 'Digital Mosaic', 'Kiss', 'Solowork', 'Subjectivity'], $jav->tags->pluck('name')->sort()->values()->toArray());
        $this->assertEquals(['Arisa Shindo'], $jav->actors->pluck('name')->sort()->values()->toArray());
    }

    public function test_duplicate_handling_updates_existing_record(): void
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

        $jav1 = $this->manager->store($item, 'feature-test');
        $originalId = $jav1->id;

        // Same code + source = update
        $updatedItem = new Item(
            id: 'tek074',
            title: 'TEK074 - Updated',
            url: '/torrent/tek074',
            image: 'https://example.com/tek074-v2.jpg',
            date: Carbon::parse('2016-10-16'),
            code: 'TEK074',
            tags: collect(['Updated']),
            size: 2.0,
            description: 'Updated',
            actresses: collect(['Miharu Usa', 'Another']),
            download: '/torrent/tek074/download/22345950/onejav.com_tek074.torrent'
        );

        $jav2 = $this->manager->store($updatedItem, 'feature-test');

        $this->assertEquals($originalId, $jav2->id);
        $this->assertEquals('TEK074 - Updated', $jav2->title);
        $this->assertEquals('Updated', $jav2->description);
        $this->assertEquals(2.0, $jav2->size);
        $this->assertEquals(['Updated'], $jav2->tags->pluck('name')->values()->toArray());
        $this->assertEquals(['Another', 'Miharu Usa'], $jav2->actors->pluck('name')->sort()->values()->toArray());

        $this->assertEquals(1, Jav::where('code', 'TEK074')->where('source', 'feature-test')->count());
    }

    public function test_store_creates_separate_records_for_same_code_different_source(): void
    {
        $item = new Item(
            id: 'abp462',
            title: 'ABP462',
            url: '/torrent/abp462',
            image: 'https://example.com/image.jpg',
            date: Carbon::parse('2016-10-16'),
            code: 'ABP462',
            tags: collect(['Lingerie']),
            size: 1.2,
            description: 'Test description',
            actresses: collect(['Nao Wakana']),
            download: '/torrent/abp462/download/91625328/onejav.com_abp462.torrent'
        );

        $jav1 = $this->manager->store($item, 'manager-test');
        $jav2 = $this->manager->store($item, 'manager-test-2');

        $this->assertNotEquals($jav1->id, $jav2->id);
        $this->assertEquals('manager-test', $jav1->source);
        $this->assertEquals('manager-test-2', $jav2->source);

        $this->assertEquals(1, Jav::where('code', 'ABP462')->where('source', 'manager-test')->count());
        $this->assertEquals(1, Jav::where('code', 'ABP462')->where('source', 'manager-test-2')->count());
    }

    public function test_store_handles_null_fields_gracefully(): void
    {
        $item = new Item(
            id: 'test1234',
            title: 'TEST1234',
            url: '/torrent/test1234',
            image: null,
            date: null,
            code: 'TEST1234',
            tags: collect([]),
            size: null,
            description: null,
            actresses: collect([]),
            download: null
        );

        $jav = $this->manager->store($item, 'manager-test');

        $this->assertInstanceOf(Jav::class, $jav);
        $this->assertNull($jav->image);
        $this->assertNull($jav->date);
        $this->assertNull($jav->size);
        $this->assertNull($jav->description);
        $this->assertNull($jav->download);
        $this->assertEmpty($jav->tags);
        $this->assertEmpty($jav->actors);
    }
}
