<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Repositories;

use Modules\JAV\Models\MongoDb\Onejav;
use Modules\JAV\Repositories\OnejavRepository;
use Modules\JAV\Tests\TestCase;

final class OnejavRepositoryTest extends TestCase
{
    private OnejavRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new OnejavRepository();
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

        $doc = $this->repository->upsertByCode('ONEJAV-001', [
            'movie' => ['title' => 'Test Movie', 'description' => 'Desc'],
            'tags' => [['name' => 'Tag1']],
            'actors' => [['name' => 'Actor One']],
        ]);

        $this->assertInstanceOf(Onejav::class, $doc);
        $this->assertSame('ONEJAV-001', $doc->code);
        $this->assertSame('Test Movie', $doc->movie['title'] ?? null);
        $this->assertCount(1, $doc->tags);
        $this->assertCount(1, $doc->actors);
    }

    public function test_upsert_by_code_updates_existing(): void
    {
        $this->assertMongoAvailable();

        Onejav::create([
            'code' => 'ONEJAV-002',
            'movie' => ['title' => 'Old'],
            'tags' => [],
            'actors' => [],
        ]);

        $doc = $this->repository->upsertByCode('ONEJAV-002', [
            'movie' => ['title' => 'Updated Title'],
            'tags' => [],
            'actors' => [],
        ]);

        $this->assertSame('Updated Title', $doc->movie['title'] ?? null);
        $this->assertSame(1, Onejav::query()->where('code', 'ONEJAV-002')->count());
    }

    public function test_find_by_code_returns_document(): void
    {
        $this->assertMongoAvailable();

        $this->repository->upsertByCode('ONEJAV-003', [
            'movie' => ['title' => 'Find Me'],
            'tags' => [],
            'actors' => [],
        ]);

        $found = $this->repository->findByCode('ONEJAV-003');

        $this->assertInstanceOf(Onejav::class, $found);
        $this->assertSame('ONEJAV-003', $found->code);
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
            Onejav::query()->limit(1)->get();
        } catch (\Throwable $e) {
            $this->markTestSkipped('MongoDB is not reachable: ' . $e->getMessage());
        }
    }

    private function cleanCollection(): void
    {
        try {
            Onejav::query()->delete();
        } catch (\Throwable) {
            // ignore
        }
    }
}
