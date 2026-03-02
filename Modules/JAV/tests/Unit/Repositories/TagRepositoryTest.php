<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Tag;
use Modules\JAV\Repositories\TagRepository;
use Modules\JAV\Tests\TestCase;

final class TagRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TagRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TagRepository();
    }

    public function test_happy_upsert_by_name_creates_new_tag(): void
    {
        $tag = $this->repository->upsertByName('Drama');

        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertSame('Drama', $tag->name);
        $this->assertNotNull($tag->uuid);
        $this->assertDatabaseHas('tags', ['name' => 'Drama']);
    }

    public function test_happy_upsert_by_name_updates_description(): void
    {
        Tag::factory()->create(['name' => 'Comedy', 'description' => null]);

        $tag = $this->repository->upsertByName('Comedy', ['description' => 'Funny stuff']);

        $this->assertSame('Funny stuff', $tag->description);
    }

    public function test_happy_find_by_name_returns_tag(): void
    {
        Tag::factory()->create(['name' => 'Action']);

        $found = $this->repository->findByName('Action');

        $this->assertInstanceOf(Tag::class, $found);
        $this->assertSame('Action', $found->name);
    }

    public function test_unhappy_find_by_name_returns_null(): void
    {
        $this->assertNull($this->repository->findByName('NonExistentTag'));
    }
}
