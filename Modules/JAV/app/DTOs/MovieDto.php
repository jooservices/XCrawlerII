<?php

declare(strict_types=1);

namespace Modules\JAV\DTOs;

use Modules\JAV\Enums\SourceEnum;

final readonly class MovieDto
{
    /**
     * @param  array<int, ActorDto>  $actors
     * @param  array<int, TagDto>  $tags
     */
    public function __construct(
        public SourceEnum $source,
        public string $code,
        public ?string $itemId = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $category = null,
        public ?string $cover = null,
        public ?string $trailer = null,
        public ?array $gallery = null,
        public ?bool $isCensored = null,
        public ?bool $hasSubtitles = null,
        public ?array $subtitles = null,
        public ?\DateTimeInterface $releaseDate = null,
        public ?int $durationMinutes = null,
        public ?\DateTimeInterface $crawledAt = null,
        public ?\DateTimeInterface $seenAt = null,
        public ?array $attributes = null,
        public array $actors = [],
        public array $tags = [],
    ) {
    }
}
