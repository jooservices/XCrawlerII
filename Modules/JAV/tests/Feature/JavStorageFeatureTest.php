<?php

namespace Modules\JAV\Tests\Feature;

use Carbon\Carbon;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Listeners\JavSubscriber;
use Modules\JAV\Models\Jav;
use Modules\JAV\Services\JavManager;
use Modules\JAV\Tests\TestCase;

/**
 * Feature test that writes REAL data to the database.
 * No RefreshDatabase - data persists for manual inspection.
 */
class JavStorageFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Jav::where('source', 'feature-test')->delete();
    }

    public function test_manager_stores_real_data(): void
    {
        $manager = new JavManager();

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

        $jav = $manager->store($item, 'feature-test');

        $this->assertInstanceOf(Jav::class, $jav);
        $this->assertNotNull($jav->id);
        $this->assertEquals('ABP462', $jav->code);
        $this->assertEquals('feature-test', $jav->source);
        $this->assertEquals(1.2, $jav->size);
        $this->assertEquals(['Lingerie', 'Masturbation', 'Pantyhose', 'Solowork', 'Toy'], $jav->tags);
        $this->assertEquals(['Nao Wakana'], $jav->actresses);
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
        $this->assertEquals(['Beautiful Girl', 'Digital Mosaic', 'Kiss', 'Solowork', 'Subjectivity'], $jav->tags);
        $this->assertEquals(['Arisa Shindo'], $jav->actresses);
    }

    public function test_duplicate_handling(): void
    {
        $manager = new JavManager();

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

        $jav1 = $manager->store($item, 'feature-test');
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

        $jav2 = $manager->store($updatedItem, 'feature-test');

        $this->assertEquals($originalId, $jav2->id);
        $this->assertEquals('TEK074 - Updated', $jav2->title);
        $this->assertEquals(1, Jav::where('code', 'TEK074')->where('source', 'feature-test')->count());
    }
}
