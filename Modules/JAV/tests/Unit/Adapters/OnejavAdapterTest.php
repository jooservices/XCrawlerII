<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Adapters;

use Modules\JAV\DTOs\ActorDto;
use Modules\JAV\DTOs\MovieDto;
use Modules\JAV\DTOs\TagDto;
use Modules\JAV\Enums\SourceEnum;
use Modules\JAV\Models\MongoDb\Onejav;
use Modules\JAV\Services\Providers\OnejavAdapter;
use Modules\JAV\Tests\TestCase;

final class OnejavAdapterTest extends TestCase
{
    private OnejavAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = app(OnejavAdapter::class);
        $this->cleanMongoCollections();
    }

    protected function tearDown(): void
    {
        $this->cleanMongoCollections();
        parent::tearDown();
    }

    public function test_happy_save_snapshot_creates_document(): void
    {
        $this->assertMongoAvailable();

        $dto = new MovieDto(
            source: SourceEnum::Onejav,
            code: 'TEST-001',
            title: 'Snapshot Movie',
            actors: [new ActorDto(name: 'Actor One')],
            tags: [new TagDto(name: 'Tag1', description: 'Desc')],
        );

        $this->adapter->save($dto);

        $doc = Onejav::query()->where('code', 'TEST-001')->first();
        $this->assertNotNull($doc);
        $this->assertSame('TEST-001', $doc->code);
        $this->assertIsArray($doc->movie);
        $this->assertSame('Snapshot Movie', $doc->movie['title'] ?? null);
        $this->assertIsArray($doc->tags);
        $this->assertCount(1, $doc->tags);
        $this->assertIsArray($doc->actors);
        $this->assertCount(1, $doc->actors);
    }

    public function test_happy_save_snapshot_updates_existing_by_code(): void
    {
        $this->assertMongoAvailable();

        Onejav::factory()->create([
            'code' => 'UPD-002',
            'movie' => ['title' => 'Old'],
            'tags' => [],
            'actors' => [],
        ]);

        $dto = new MovieDto(source: SourceEnum::Onejav, code: 'UPD-002', title: 'New Title');
        $this->adapter->save($dto);

        $doc = Onejav::query()->where('code', 'UPD-002')->first();
        $this->assertNotNull($doc);
        $this->assertSame('New Title', $doc->movie['title'] ?? null);
        $this->assertSame(1, Onejav::query()->where('code', 'UPD-002')->count());
    }

    private function assertMongoAvailable(): void
    {
        try {
            Onejav::query()->limit(1)->get();
        } catch (\Throwable $e) {
            $this->markTestSkipped('MongoDB is not reachable: ' . $e->getMessage());
        }
    }

    private function cleanMongoCollections(): void
    {
        try {
            Onejav::query()->delete();
        } catch (\Throwable) {
            // ignore
        }
    }
}
