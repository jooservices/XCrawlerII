<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\DTOs;

use Modules\JAV\DTOs\ActorDto;
use Modules\JAV\DTOs\MovieDto;
use Modules\JAV\DTOs\TagDto;
use Modules\JAV\Enums\SourceEnum;
use Modules\JAV\Tests\TestCase;

final class MovieDtoTest extends TestCase
{
    public function test_constructor_with_required_fields_only(): void
    {
        $dto = new MovieDto(source: SourceEnum::Onejav, code: 'ABC-123');

        $this->assertSame(SourceEnum::Onejav, $dto->source);
        $this->assertSame('ABC-123', $dto->code);
        $this->assertNull($dto->itemId);
        $this->assertNull($dto->title);
        $this->assertSame([], $dto->actors);
        $this->assertSame([], $dto->tags);
    }

    public function test_constructor_with_all_fields(): void
    {
        $releaseDate = new \DateTimeImmutable('2025-01-15');
        $crawledAt = new \DateTimeImmutable('2025-01-16 10:00:00');
        $actors = [new ActorDto(name: 'Actor A')];
        $tags = [new TagDto(name: 'Tag1', description: 'Desc')];

        $dto = new MovieDto(
            source: SourceEnum::FfJav,
            code: 'XYZ-999',
            itemId: 'item-1',
            title: 'Title',
            description: 'Desc',
            category: 'Cat',
            cover: 'https://example.com/cover.jpg',
            trailer: 'https://example.com/trailer',
            gallery: ['a.jpg', 'b.jpg'],
            isCensored: true,
            hasSubtitles: false,
            subtitles: [],
            releaseDate: $releaseDate,
            durationMinutes: 120,
            crawledAt: $crawledAt,
            seenAt: null,
            attributes: ['key' => 'value'],
            actors: $actors,
            tags: $tags,
        );

        $this->assertSame(SourceEnum::FfJav, $dto->source);
        $this->assertSame('XYZ-999', $dto->code);
        $this->assertSame('item-1', $dto->itemId);
        $this->assertSame('Title', $dto->title);
        $this->assertSame('Desc', $dto->description);
        $this->assertSame('Cat', $dto->category);
        $this->assertSame('https://example.com/cover.jpg', $dto->cover);
        $this->assertSame('https://example.com/trailer', $dto->trailer);
        $this->assertSame(['a.jpg', 'b.jpg'], $dto->gallery);
        $this->assertTrue($dto->isCensored);
        $this->assertFalse($dto->hasSubtitles);
        $this->assertSame([], $dto->subtitles);
        $this->assertSame($releaseDate, $dto->releaseDate);
        $this->assertSame(120, $dto->durationMinutes);
        $this->assertSame($crawledAt, $dto->crawledAt);
        $this->assertNull($dto->seenAt);
        $this->assertSame(['key' => 'value'], $dto->attributes);
        $this->assertSame($actors, $dto->actors);
        $this->assertSame($tags, $dto->tags);
    }

    public function test_constructor_with_empty_actors_and_tags(): void
    {
        $dto = new MovieDto(source: SourceEnum::OneFourOneJav, code: 'C-001', actors: [], tags: []);

        $this->assertSame([], $dto->actors);
        $this->assertSame([], $dto->tags);
    }
}
