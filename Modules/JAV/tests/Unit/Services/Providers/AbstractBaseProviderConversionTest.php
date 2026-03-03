<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Services\Providers;

use Modules\JAV\Contracts\Repositories\MovieRepositoryInterface;
use Modules\JAV\DTOs\ActorDto;
use Modules\JAV\DTOs\MovieDto;
use Modules\JAV\DTOs\TagDto;
use Modules\JAV\Enums\SourceEnum;
use Modules\JAV\Services\Providers\AbstractBaseProvider;
use Modules\JAV\Tests\TestCase;

final class AbstractBaseProviderConversionTest extends TestCase
{
    public function test_dto_to_movie_array_formats_release_date_as_y_m_d(): void
    {
        $capturing = new CapturingMovieRepository();
        $provider = new TestAbstractBaseProvider($capturing);

        $releaseDate = new \DateTimeImmutable('2025-06-15');
        $dto = new MovieDto(
            source: SourceEnum::Onejav,
            code: 'DT-001',
            releaseDate: $releaseDate,
        );

        $provider->save($dto);

        $this->assertSame('2025-06-15', $capturing->lastPayload['movie']['release_date'] ?? null);
    }

    public function test_dto_to_movie_array_formats_crawled_at_and_seen_at_as_atom(): void
    {
        $capturing = new CapturingMovieRepository();
        $provider = new TestAbstractBaseProvider($capturing);

        $crawledAt = new \DateTimeImmutable('2025-01-10 14:30:00');
        $seenAt = new \DateTimeImmutable('2025-01-11 09:00:00');
        $dto = new MovieDto(
            source: SourceEnum::Onejav,
            code: 'DT-002',
            crawledAt: $crawledAt,
            seenAt: $seenAt,
        );

        $provider->save($dto);

        $this->assertStringContainsString('2025-01-10', $capturing->lastPayload['movie']['crawled_at'] ?? '');
        $this->assertStringContainsString('2025-01-11', $capturing->lastPayload['movie']['seen_at'] ?? '');
    }

    public function test_dto_to_movie_array_handles_null_dates(): void
    {
        $capturing = new CapturingMovieRepository();
        $provider = new TestAbstractBaseProvider($capturing);

        $dto = new MovieDto(
            source: SourceEnum::Onejav,
            code: 'DT-003',
            releaseDate: null,
            crawledAt: null,
            seenAt: null,
        );

        $provider->save($dto);

        $movie = $capturing->lastPayload['movie'];
        $this->assertNull($movie['release_date'] ?? null);
        $this->assertNull($movie['crawled_at'] ?? null);
        $this->assertNull($movie['seen_at'] ?? null);
    }

    public function test_dto_to_tags_array_includes_name_and_description(): void
    {
        $capturing = new CapturingMovieRepository();
        $provider = new TestAbstractBaseProvider($capturing);

        $dto = new MovieDto(
            source: SourceEnum::Onejav,
            code: 'DT-004',
            tags: [
                new TagDto(name: 'Tag1', description: 'Desc1'),
                new TagDto(name: 'Tag2', description: null),
            ],
        );

        $provider->save($dto);

        $tags = $capturing->lastPayload['tags'];
        $this->assertCount(2, $tags);
        $this->assertSame('Tag1', $tags[0]['name']);
        $this->assertSame('Desc1', $tags[0]['description']);
        $this->assertSame('Tag2', $tags[1]['name']);
        $this->assertNull($tags[1]['description']);
    }

    public function test_dto_to_tags_array_handles_empty_tags(): void
    {
        $capturing = new CapturingMovieRepository();
        $provider = new TestAbstractBaseProvider($capturing);

        $dto = new MovieDto(source: SourceEnum::Onejav, code: 'DT-005', tags: []);

        $provider->save($dto);

        $this->assertSame([], $capturing->lastPayload['tags']);
    }

    public function test_dto_to_actors_array_formats_birth_date_and_crawled_at(): void
    {
        $capturing = new CapturingMovieRepository();
        $provider = new TestAbstractBaseProvider($capturing);

        $birthDate = new \DateTimeImmutable('1990-05-20');
        $crawledAt = new \DateTimeImmutable('2025-02-01 12:00:00');
        $dto = new MovieDto(
            source: SourceEnum::Onejav,
            code: 'DT-006',
            actors: [
                new ActorDto(name: 'Actor A', birthDate: $birthDate, crawledAt: $crawledAt),
            ],
        );

        $provider->save($dto);

        $actors = $capturing->lastPayload['actors'];
        $this->assertCount(1, $actors);
        $this->assertSame('1990-05-20', $actors[0]['birth_date'] ?? null);
        $this->assertStringContainsString('2025-02-01', $actors[0]['crawled_at'] ?? '');
    }

    public function test_dto_to_actors_array_handles_null_actor_fields(): void
    {
        $capturing = new CapturingMovieRepository();
        $provider = new TestAbstractBaseProvider($capturing);

        $dto = new MovieDto(
            source: SourceEnum::Onejav,
            code: 'DT-007',
            actors: [new ActorDto(name: 'Minimal Actor')],
        );

        $provider->save($dto);

        $actors = $capturing->lastPayload['actors'];
        $this->assertCount(1, $actors);
        $this->assertSame('Minimal Actor', $actors[0]['name']);
        $this->assertNull($actors[0]['avatar'] ?? null);
        $this->assertNull($actors[0]['birth_date'] ?? null);
        $this->assertNull($actors[0]['crawled_at'] ?? null);
    }

    public function test_dto_to_actors_array_handles_empty_actors(): void
    {
        $capturing = new CapturingMovieRepository();
        $provider = new TestAbstractBaseProvider($capturing);

        $dto = new MovieDto(source: SourceEnum::Onejav, code: 'DT-008', actors: []);

        $provider->save($dto);

        $this->assertSame([], $capturing->lastPayload['actors']);
    }
}

/**
 * Captures the last payload passed to upsertByCode for assertion in tests.
 */
final class CapturingMovieRepository implements MovieRepositoryInterface
{
    public ?string $lastCode = null;

    /** @var array<string, mixed>|null */
    public ?array $lastPayload = null;

    public function upsertByCode(string $code, array $attributes): object
    {
        $this->lastCode = $code;
        $this->lastPayload = $attributes;

        return new \stdClass();
    }

    public function findByCode(string $code): ?object
    {
        return null;
    }
}

/**
 * Test double that exposes AbstractBaseProvider by delegating to a custom repository.
 */
final class TestAbstractBaseProvider extends AbstractBaseProvider
{
    public function __construct(
        private readonly MovieRepositoryInterface $repository,
    ) {
    }

    protected function repository(): object
    {
        return $this->repository;
    }
}
