<?php

namespace Modules\JAV\Tests\Unit\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Models\Jav;
use Modules\JAV\Services\JavManager;
use Modules\JAV\Tests\TestCase;

class JavManagerTest extends TestCase
{
    protected JavManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new JavManager();

        Jav::where('source', 'manager-test')->delete();
    }

    public function test_store_creates_new_item(): void
    {
        $item = new Item(
            id: 'abp462',
            title: 'ABP462',
            url: '/torrent/abp462',
            image: 'https://example.com/image.jpg',
            date: Carbon::parse('2016-10-16'),
            code: 'ABP462',
            tags: collect(['Lingerie', 'Masturbation']),
            size: 1.2,
            description: 'Test description',
            actresses: collect(['Nao Wakana']),
            download: '/torrent/abp462/download/91625328/onejav.com_abp462.torrent'
        );

        $jav = $this->manager->store($item, 'manager-test');

        $this->assertInstanceOf(Jav::class, $jav);
        $this->assertDatabaseHas('jav', [
            'item_id' => 'abp462',
            'code' => 'ABP462',
            'title' => 'ABP462',
            'url' => '/torrent/abp462',
            'source' => 'manager-test',
        ]);

        // Verify relationships
        $this->assertEquals(['Lingerie', 'Masturbation'], $jav->tags->pluck('name')->sort()->values()->toArray());
        $this->assertEquals(['Nao Wakana'], $jav->actors->pluck('name')->sort()->values()->toArray());
        $this->assertEquals(1.2, $jav->size);
        $this->assertEquals('2016-10-16', $jav->date->format('Y-m-d'));
    }

    public function test_store_updates_existing_item_with_same_code_and_source(): void
    {
        $item1 = new Item(
            id: 'abp462',
            title: 'ABP462',
            url: '/torrent/abp462',
            image: 'https://example.com/image.jpg',
            date: Carbon::parse('2016-10-16'),
            code: 'ABP462',
            tags: collect(['Lingerie']),
            size: 1.2,
            description: 'Original description',
            actresses: collect(['Nao Wakana']),
            download: '/torrent/abp462/download/91625328/onejav.com_abp462.torrent'
        );

        $jav1 = $this->manager->store($item1, 'manager-test');
        $originalId = $jav1->id;

        $item2 = new Item(
            id: 'abp462',
            title: 'ABP462 Updated',
            url: '/torrent/abp462',
            image: 'https://example.com/image2.jpg',
            date: Carbon::parse('2016-10-16'),
            code: 'ABP462',
            tags: collect(['Lingerie', 'Masturbation']),
            size: 1.5,
            description: 'Updated description',
            actresses: collect(['Nao Wakana', 'Another Actress']),
            download: '/torrent/abp462/download/91625328/onejav.com_abp462.torrent'
        );

        $jav2 = $this->manager->store($item2, 'manager-test');

        $this->assertEquals($originalId, $jav2->id);
        $this->assertEquals('ABP462 Updated', $jav2->title);
        $this->assertEquals('Updated description', $jav2->description);
        $this->assertEquals(1.5, $jav2->size);
        $this->assertEquals(['Lingerie', 'Masturbation'], $jav2->tags->pluck('name')->sort()->values()->toArray());
        $this->assertEquals(['Another Actress', 'Nao Wakana'], $jav2->actors->pluck('name')->sort()->values()->toArray());

        $this->assertEquals(1, Jav::where('code', 'ABP462')->where('source', 'manager-test')->count());
    }

    public function test_store_creates_separate_records_for_same_code_different_source(): void
    {
        Jav::where('source', 'manager-test-2')->delete();

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
    }

    public function test_store_handles_null_fields(): void
    {
        $item = new Item(
            id: 'test123',
            title: 'TEST123',
            url: '/torrent/test123',
            image: null,
            date: null,
            code: 'TEST123',
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

    public function test_store_handles_empty_collections(): void
    {
        $item = new Item(
            id: 'test456',
            title: 'TEST456',
            url: '/torrent/test456',
            image: 'https://example.com/image.jpg',
            date: Carbon::parse('2016-10-16'),
            code: 'TEST456',
            tags: new Collection(),
            size: 1.0,
            description: 'Test',
            actresses: new Collection(),
            download: '/download'
        );

        $jav = $this->manager->store($item, 'manager-test');

        $this->assertInstanceOf(Jav::class, $jav);
        $this->assertEmpty($jav->tags);
        $this->assertEmpty($jav->actors);
    }
}
