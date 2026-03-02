<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Feature\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\DTOs\ActorDto;
use Modules\JAV\DTOs\MovieDto;
use Modules\JAV\DTOs\TagDto;
use Modules\JAV\Enums\SourceEnum;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Movie;
use Modules\JAV\Models\MongoDb\FfJav;
use Modules\JAV\Models\MongoDb\OneFourOneJav;
use Modules\JAV\Models\MongoDb\Onejav;
use Modules\JAV\Repositories\MovieRepository;
use Modules\JAV\Services\MovieService;
use Modules\JAV\Tests\TestCase;

/**
 * Full flow: MovieService::save() -> adapter (Mongo snapshot) + transaction (MySQL + pivot sync). No mocks of internal services.
 */
final class MovieServiceTest extends TestCase
{
    use RefreshDatabase;

    private MovieService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MovieService::class);
        $this->cleanMongoCollections();
    }

    protected function tearDown(): void
    {
        $this->cleanMongoCollections();
        parent::tearDown();
    }

    public function test_save_persists_movie_actors_tags_and_syncs_pivots(): void
    {
        $dto = new MovieDto(
            source: SourceEnum::Onejav,
            code: 'ABP-123',
            title: 'ABP-123 Realistic Fixture',
            actors: [
                new ActorDto(name: 'Yua Mikami'),
                new ActorDto(name: 'Aoi Tsukasa'),
            ],
            tags: [
                new TagDto(name: 'Drama'),
                new TagDto(name: 'School Girl', description: '制服テーマ'),
            ],
        );

        $this->service->save($dto);

        $this->assertDatabaseHas('movies', ['code' => 'ABP-123']);
        $movie = app(MovieRepository::class)->findByCode('ABP-123');
        $this->assertInstanceOf(Movie::class, $movie);
        $this->assertSame('ABP-123', $movie->code);
        $this->assertSame('ABP-123 Realistic Fixture', $movie->title);

        $movie->load(['actors', 'tags']);
        $this->assertCount(2, $movie->actors);
        $this->assertCount(2, $movie->tags);

        $actorNames = $movie->actors->pluck('name')->sort()->values()->all();
        $this->assertSame(['Aoi Tsukasa', 'Yua Mikami'], $actorNames);
        $tagNames = $movie->tags->pluck('name')->sort()->values()->all();
        $this->assertSame(['Drama', 'School Girl'], $tagNames);

        $this->assertDatabaseCount('movie_actor', 2);
        $this->assertDatabaseCount('movie_tag', 2);
    }

    public function test_save_writes_mongo_snapshot_for_onejav(): void
    {
        $this->assertMongoAvailable();

        $dto = new MovieDto(source: SourceEnum::Onejav, code: 'SSIS-777', title: 'SSIS-777 Sample');
        $this->service->save($dto);

        $doc = Onejav::query()->where('code', 'SSIS-777')->first();
        $this->assertNotNull($doc);
        $this->assertSame('SSIS-777 Sample', $doc->movie['title'] ?? null);
    }

    public function test_save_writes_mongo_snapshot_for_141jav(): void
    {
        $this->assertMongoAvailable();

        $dto = new MovieDto(source: SourceEnum::OneFourOneJav, code: 'IPX-901', title: 'IPX-901 Sample');
        $this->service->save($dto);

        $doc = OneFourOneJav::query()->where('code', 'IPX-901')->first();
        $this->assertNotNull($doc);
        $this->assertSame('IPX-901 Sample', $doc->movie['title'] ?? null);
    }

    public function test_save_writes_mongo_snapshot_for_ffjav(): void
    {
        $this->assertMongoAvailable();

        $dto = new MovieDto(source: SourceEnum::FfJav, code: 'MIDE-654', title: 'MIDE-654 Sample');
        $this->service->save($dto);

        $doc = FfJav::query()->where('code', 'MIDE-654')->first();
        $this->assertNotNull($doc);
        $this->assertSame('MIDE-654 Sample', $doc->movie['title'] ?? null);
    }

    public function test_save_updates_existing_movie_and_syncs_pivots(): void
    {
        $movie = Movie::factory()->create(['code' => 'MEYD-111', 'title' => 'Before']);
        $actor = Actor::factory()->create(['name' => 'Yui Hatano']);
        $movie->actors()->attach($actor->id);

        $dto = new MovieDto(
            source: SourceEnum::Onejav,
            code: 'MEYD-111',
            title: 'After',
            actors: [new ActorDto(name: 'Kana Momonogi')],
            tags: [new TagDto(name: 'Office Lady')],
        );
        $this->service->save($dto);

        $updated = app(MovieRepository::class)->findByCode('MEYD-111');
        $this->assertSame($movie->id, $updated->id);
        $this->assertSame('After', $updated->title);
        $updated->load(['actors', 'tags']);
        $this->assertCount(1, $updated->actors);
        $this->assertSame('Kana Momonogi', $updated->actors->first()?->name);
        $this->assertCount(1, $updated->tags);
        $this->assertSame('Office Lady', $updated->tags->first()?->name);
    }

    public function test_save_with_no_actors_and_no_tags(): void
    {
        $dto = new MovieDto(source: SourceEnum::Onejav, code: 'JUQ-222', title: 'No Relations', actors: [], tags: []);
        $this->service->save($dto);

        $movie = app(MovieRepository::class)->findByCode('JUQ-222');
        $this->assertInstanceOf(Movie::class, $movie);
        $this->assertSame('JUQ-222', $movie->code);
        $movie->load(['actors', 'tags']);
        $this->assertCount(0, $movie->actors);
        $this->assertCount(0, $movie->tags);
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
            OneFourOneJav::query()->delete();
            FfJav::query()->delete();
        } catch (\Throwable) {
            // ignore
        }
    }
}
