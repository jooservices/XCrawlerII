<?php

declare(strict_types=1);

namespace Modules\JAV\Services\Crawling\Crawlers\Onejav;

use Modules\JAV\DTOs\ActorDto;
use Modules\JAV\DTOs\MovieDto;
use Modules\JAV\DTOs\TagDto;
use Modules\JAV\Enums\SourceEnum;
use Modules\JAV\Support\CodeNormalizer;
use Symfony\Component\DomCrawler\Crawler;

final readonly class Item
{
    public function __construct(
        private Crawler $node,
        private SourceEnum $source,
    ) {
    }

    public function toMovie(): MovieDto
    {
        return new MovieDto(
            source: $this->source,
            code: $this->extractCode(),
            title: $this->extractTitle(),
            cover: $this->extractCover(),
            releaseDate: $this->extractReleaseDate(),
            actors: $this->extractActors(),
            tags: $this->extractTags(),
        );
    }

    private function extractCode(): string
    {
        $link = $this->node->filter('h5.title a')->first();

        if ($link->count() === 0) {
            return '';
        }

        $href = $link->attr('href') ?? '';
        if ($href !== '') {
            $segment = basename($href);

            return CodeNormalizer::normalize($segment) ?? $segment;
        }

        $raw = trim($link->text() ?? '');

        return CodeNormalizer::normalize($raw) ?? $raw;
    }

    private function extractTitle(): ?string
    {
        $el = $this->node->filter('.level.has-text-grey-dark')->first();
        if ($el->count() === 0) {
            return null;
        }

        $text = trim($el->text() ?? '');

        return $text !== '' ? $text : null;
    }

    private function extractCover(): ?string
    {
        $img = $this->node->filter('.column img.image')->first();
        if ($img->count() === 0) {
            return null;
        }

        $src = $img->attr('src');

        return $src !== null && $src !== '' ? $src : null;
    }

    private function extractReleaseDate(): ?\DateTimeInterface
    {
        $link = $this->node->filter('p.subtitle a')->first();
        if ($link->count() === 0) {
            return null;
        }

        $href = $link->attr('href') ?? '';
        if (preg_match('#^/(\d{4})/(\d{2})/(\d{2})$#', $href, $m)) {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', "{$m[1]}-{$m[2]}-{$m[3]}");
            if ($date !== false) {
                return $date;
            }
        }

        $text = trim($link->text() ?? '');
        if ($text !== '') {
            $parsed = \DateTimeImmutable::createFromFormat('F j, Y', $text);
            if ($parsed !== false) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * @return array<int, TagDto>
     */
    private function extractTags(): array
    {
        $tags = [];
        $this->node->filter('.tags a.tag')->each(function (Crawler $a) use (&$tags): void {
            $name = trim($a->text() ?? '');
            if ($name !== '') {
                $tags[] = new TagDto(name: $name);
            }
        });

        return $tags;
    }

    /**
     * @return array<int, ActorDto>
     */
    private function extractActors(): array
    {
        $actors = [];
        $this->node->filter('.panel .panel-block')->each(function (Crawler $block) use (&$actors): void {
            $name = trim($block->text() ?? '');
            if ($name !== '') {
                $actors[] = new ActorDto(name: $name);
            }
        });

        return $actors;
    }
}
