<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Repositories;

use Modules\JAV\Models\MongoDb\OneFourOneJav;
use Modules\JAV\Repositories\OneFourOneJavRepository;
use Modules\JAV\Tests\TestCase;

final class OneFourOneJavRepositoryTest extends TestCase
{
    private OneFourOneJavRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new OneFourOneJavRepository();
        $this->cleanCollection();
    }

    protected function tearDown(): void
    {
        $this->cleanCollection();
        parent::tearDown();
    }

    public function test_upsert_by_code_creates_document(): void
    {
        $this->assertMongoAvailable();

        $doc = $this->repository->upsertByCode('141JAV-001', [
            'movie' => ['title' => '141 Test Movie'],
            'tags' => [['name' => 'Tag1']],
            'actors' => [['name' => 'Actor One']],
        ]);

        $this->assertInstanceOf(OneFourOneJav::class, $doc);
        $this->assertSame('141JAV-001', $doc->code);
        $this->assertSame('141 Test Movie', $doc->movie['title'] ?? null);
        $this->assertCount(1, $doc->tags);
        $this->assertCount(1, $doc->actors);
    }

    public function test_upsert_by_code_updates_existing(): void
    {
        $this->assertMongoAvailable();

        OneFourOneJav::create([
            'code' => '141JAV-002',
            'movie' => ['title' => 'Old'],
            'tags' => [],
            'actors' => [],
        ]);

        $doc = $this->repository->upsertByCode('141JAV-002', [
            'movie' => ['title' => 'Updated Title'],
            'tags' => [],
            'actors' => [],
        ]);

        $this->assertSame('Updated Title', $doc->movie['title'] ?? null);
        $this->assertSame(1, OneFourOneJav::query()->where('code', '141JAV-002')->count());
    }

    public function test_find_by_code_returns_document(): void
    {
        $this->assertMongoAvailable();

        $this->repository->upsertByCode('141JAV-003', [
            'movie' => ['title' => 'Find Me'],
            'tags' => [],
            'actors' => [],
        ]);

        $found = $this->repository->findByCode('141JAV-003');

        $this->assertInstanceOf(OneFourOneJav::class, $found);
        $this->assertSame('141JAV-003', $found->code);
        $this->assertSame('Find Me', $found->movie['title'] ?? null);
    }

    public function test_find_by_code_returns_null_when_not_found(): void
    {
        $this->assertMongoAvailable();

        $found = $this->repository->findByCode('NONEXISTENT-999');

        $this->assertNull($found);
    }

    private function assertMongoAvailable(): void
    {
        try {
            OneFourOneJav::query()->limit(1)->get();
        } catch (\Throwable $e) {
            $this->markTestSkipped('MongoDB is not reachable: ' . $e->getMessage());
        }
    }

    private function cleanCollection(): void
    {
        try {
            OneFourOneJav::query()->delete();
        } catch (\Throwable) {
            // ignore
        }
    }
}
