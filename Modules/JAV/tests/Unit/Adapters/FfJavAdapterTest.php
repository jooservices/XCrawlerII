<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Adapters;

use Modules\JAV\DTOs\MovieDto;
use Modules\JAV\Enums\SourceEnum;
use Modules\JAV\Models\MongoDb\FfJav;
use Modules\JAV\Services\Providers\FfJavAdapter;
use Modules\JAV\Tests\TestCase;

final class FfJavAdapterTest extends TestCase
{
    private FfJavAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = app(FfJavAdapter::class);
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

        $dto = new MovieDto(source: SourceEnum::FfJav, code: 'FF-TEST-001', title: 'FF Movie');
        $this->adapter->save($dto);

        $doc = FfJav::query()->where('code', 'FF-TEST-001')->first();
        $this->assertNotNull($doc);
        $this->assertSame('FF-TEST-001', $doc->code);
        $this->assertSame('FF Movie', $doc->movie['title'] ?? null);
    }

    public function test_happy_save_snapshot_updates_existing_by_code(): void
    {
        $this->assertMongoAvailable();

        FfJav::factory()->create([
            'code' => 'FF-UPD',
            'movie' => ['title' => 'Old'],
            'tags' => [],
            'actors' => [],
        ]);

        $this->adapter->save(new MovieDto(source: SourceEnum::FfJav, code: 'FF-UPD', title: 'FF Updated'));
        $doc = FfJav::query()->where('code', 'FF-UPD')->first();
        $this->assertSame('FF Updated', $doc->movie['title'] ?? null);
    }

    private function assertMongoAvailable(): void
    {
        try {
            FfJav::query()->limit(1)->get();
        } catch (\Throwable $e) {
            $this->markTestSkipped('MongoDB is not reachable: ' . $e->getMessage());
        }
    }

    private function cleanMongoCollections(): void
    {
        try {
            FfJav::query()->delete();
        } catch (\Throwable) {
            // ignore
        }
    }
}
