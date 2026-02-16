<?php

namespace Modules\JAV\Tests\Unit\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Events\JavStored;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Services\JavManager;
use Modules\JAV\Tests\TestCase;

class JavManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Jav::disableSearchSyncing();
        Tag::disableSearchSyncing();
        Actor::disableSearchSyncing();
    }

    public function test_store_bulk_upsert_creates_new_actors_and_tags(): void
    {
        Event::fake([JavStored::class]);

        $item = $this->makeItem('ABC-123', collect(['Alice', 'Bob']), collect(['Tag1', 'Tag2']));

        $jav = app(JavManager::class)->store($item, 'testsource');

        $this->assertDatabaseHas('actors', ['name' => 'Alice']);
        $this->assertDatabaseHas('actors', ['name' => 'Bob']);
        $this->assertDatabaseHas('tags', ['name' => 'Tag1']);
        $this->assertDatabaseHas('tags', ['name' => 'Tag2']);
        $this->assertEqualsCanonicalizing(['Alice', 'Bob'], $jav->actors()->pluck('name')->all());
        $this->assertEqualsCanonicalizing(['Tag1', 'Tag2'], $jav->tags()->pluck('name')->all());

        Event::assertDispatched(JavStored::class, function (JavStored $event) use ($jav): bool {
            return $event->javId === (int) $jav->id
                && $event->source === 'testsource'
                && $event->actorsCount === 2
                && $event->tagsCount === 2;
        });
    }

    public function test_store_bulk_upsert_links_existing_and_creates_missing(): void
    {
        Actor::query()->create(['name' => 'Alice']);
        Tag::query()->create(['name' => 'Tag1']);

        $item = $this->makeItem('DEF-456', collect(['Alice', 'Bob']), collect(['Tag1', 'Tag2']));

        $jav = app(JavManager::class)->store($item, 'testsource');

        $this->assertSame(2, Actor::query()->count());
        $this->assertSame(2, Tag::query()->count());
        $this->assertEqualsCanonicalizing(['Alice', 'Bob'], $jav->actors()->pluck('name')->all());
        $this->assertEqualsCanonicalizing(['Tag1', 'Tag2'], $jav->tags()->pluck('name')->all());
    }

    public function test_store_bulk_upsert_dedupes_and_trims_names(): void
    {
        $item = $this->makeItem('GHI-789', collect([' Alice ', 'Alice', 'Bob', '', '   ']), collect(['Tag1', 'Tag1 ', 'Tag2', '', '   ']));

        $jav = app(JavManager::class)->store($item, 'testsource');

        $this->assertSame(2, Actor::query()->count());
        $this->assertSame(2, Tag::query()->count());
        $this->assertEqualsCanonicalizing(['Alice', 'Bob'], $jav->actors()->pluck('name')->all());
        $this->assertEqualsCanonicalizing(['Tag1', 'Tag2'], $jav->tags()->pluck('name')->all());
    }

    public function test_store_bulk_upsert_handles_empty_lists(): void
    {
        $item = $this->makeItem('JKL-000', collect(), collect());

        $jav = app(JavManager::class)->store($item, 'testsource');

        $this->assertSame(0, $jav->actors()->count());
        $this->assertSame(0, $jav->tags()->count());
    }

    private function makeItem(string $code, Collection $actresses, Collection $tags): Item
    {
        return new Item(
            id: str_replace('-', '', $code),
            title: $code,
            url: '/torrent/'.strtolower(str_replace('-', '', $code)),
            image: 'https://example.com/'.$code.'.jpg',
            date: now(),
            code: $code,
            tags: $tags,
            size: 1.2,
            description: 'desc',
            actresses: $actresses,
            download: '/download/'.$code.'.torrent'
        );
    }
}
