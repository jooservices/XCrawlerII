<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Adapters;

use Modules\JAV\DTOs\MovieDto;
use Modules\JAV\Enums\SourceEnum;
use Modules\JAV\Models\MongoDb\OneFourOneJav;
use Modules\JAV\Services\Providers\OneFourOneJavAdapter;
use Modules\JAV\Tests\TestCase;

final class OneFourOneJavAdapterTest extends TestCase
{
    private OneFourOneJavAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = app(OneFourOneJavAdapter::class);
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

        $dto = new MovieDto(source: SourceEnum::OneFourOneJav, code: '141-TEST-001', title: '141 Movie');
        $this->adapter->save($dto);

        $doc = OneFourOneJav::query()->where('code', '141-TEST-001')->first();
        $this->assertNotNull($doc);
        $this->assertSame('141-TEST-001', $doc->code);
        $this->assertSame('141 Movie', $doc->movie['title'] ?? null);
    }

    public function test_happy_save_snapshot_updates_existing_by_code(): void
    {
        $this->assertMongoAvailable();

        OneFourOneJav::factory()->create([
            'code' => '141-UPD',
            'movie' => ['title' => 'Old'],
            'tags' => [],
            'actors' => [],
        ]);

        $this->adapter->save(new MovieDto(source: SourceEnum::OneFourOneJav, code: '141-UPD', title: 'Updated'));
        $doc = OneFourOneJav::query()->where('code', '141-UPD')->first();
        $this->assertSame('Updated', $doc->movie['title'] ?? null);
    }

    private function assertMongoAvailable(): void
    {
        try {
            OneFourOneJav::query()->limit(1)->get();
        } catch (\Throwable $e) {
            $this->markTestSkipped('MongoDB is not reachable: ' . $e->getMessage());
        }
    }

    private function cleanMongoCollections(): void
    {
        try {
            OneFourOneJav::query()->delete();
        } catch (\Throwable) {
            // ignore
        }
    }
}
