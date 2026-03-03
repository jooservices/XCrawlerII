<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Repositories;

use Modules\JAV\Models\MongoDb\FfJav;
use Modules\JAV\Repositories\FfJavRepository;
use Modules\JAV\Tests\TestCase;

final class FfJavRepositoryTest extends TestCase
{
    private FfJavRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new FfJavRepository();
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

        $doc = $this->repository->upsertByCode('FFJAV-001', [
            'movie' => ['title' => 'FF Test Movie'],
            'tags' => [['name' => 'Tag1']],
            'actors' => [['name' => 'Actor One']],
        ]);

        $this->assertInstanceOf(FfJav::class, $doc);
        $this->assertSame('FFJAV-001', $doc->code);
        $this->assertSame('FF Test Movie', $doc->movie['title'] ?? null);
        $this->assertCount(1, $doc->tags);
        $this->assertCount(1, $doc->actors);
    }

    public function test_upsert_by_code_updates_existing(): void
    {
        $this->assertMongoAvailable();

        FfJav::create([
            'code' => 'FFJAV-002',
            'movie' => ['title' => 'Old'],
            'tags' => [],
            'actors' => [],
        ]);

        $doc = $this->repository->upsertByCode('FFJAV-002', [
            'movie' => ['title' => 'Updated Title'],
            'tags' => [],
            'actors' => [],
        ]);

        $this->assertSame('Updated Title', $doc->movie['title'] ?? null);
        $this->assertSame(1, FfJav::query()->where('code', 'FFJAV-002')->count());
    }

    public function test_find_by_code_returns_document(): void
    {
        $this->assertMongoAvailable();

        $this->repository->upsertByCode('FFJAV-003', [
            'movie' => ['title' => 'Find Me'],
            'tags' => [],
            'actors' => [],
        ]);

        $found = $this->repository->findByCode('FFJAV-003');

        $this->assertInstanceOf(FfJav::class, $found);
        $this->assertSame('FFJAV-003', $found->code);
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
            FfJav::query()->limit(1)->get();
        } catch (\Throwable $e) {
            $this->markTestSkipped('MongoDB is not reachable: ' . $e->getMessage());
        }
    }

    private function cleanCollection(): void
    {
        try {
            FfJav::query()->delete();
        } catch (\Throwable) {
            // ignore
        }
    }
}
