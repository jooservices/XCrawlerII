<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Movie;
use Modules\JAV\Repositories\MovieRepository;
use Modules\JAV\Tests\TestCase;

final class MovieRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private MovieRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MovieRepository();
    }

    public function test_happy_upsert_by_code_creates_new_movie(): void
    {
        $movie = $this->repository->upsertByCode('ABC-123', [
            'title' => 'Test Movie',
            'description' => 'A test',
        ]);

        $this->assertInstanceOf(Movie::class, $movie);
        $this->assertSame('ABC-123', $movie->code);
        $this->assertSame('Test Movie', $movie->title);
        $this->assertNotNull($movie->uuid);
        $this->assertDatabaseHas('movies', ['code' => 'ABC-123', 'title' => 'Test Movie']);
    }

    public function test_happy_upsert_by_code_updates_existing(): void
    {
        Movie::factory()->create(['code' => 'XYZ-999', 'title' => 'Original']);

        $movie = $this->repository->upsertByCode('XYZ-999', ['title' => 'Updated Title']);

        $this->assertSame('Updated Title', $movie->title);
        $this->assertDatabaseHas('movies', ['code' => 'XYZ-999', 'title' => 'Updated Title']);
    }

    public function test_happy_find_by_code_returns_movie(): void
    {
        Movie::factory()->create(['code' => 'FND-001']);

        $found = $this->repository->findByCode('FND-001');

        $this->assertInstanceOf(Movie::class, $found);
        $this->assertSame('FND-001', $found->code);
    }

    public function test_unhappy_find_by_code_returns_null(): void
    {
        $this->assertNull($this->repository->findByCode('nonexistent'));
    }
}
