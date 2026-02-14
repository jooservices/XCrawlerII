<?php

namespace Modules\JAV\Services\Ffjav;

use Carbon\Carbon;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Support\CodeNormalizer;
use Symfony\Component\DomCrawler\Crawler;

class ItemAdapter
{
    public function __construct(protected Crawler $node) {}

    public function getItem(): Item
    {
        $cover = $this->node->filter('.column .image')->count() ? $this->node->filter('.column .image')->attr('src') : null;

        $titleNode = $this->node->filter('.column.is-5 .card-content h5.title a');
        $title = $titleNode->count() ? trim($titleNode->text()) : null;
        $link = $titleNode->count() ? $titleNode->attr('href') : null;

        $dateText = $this->node->filter('.subtitle.is-6 a')->count()
            ? trim($this->node->filter('.subtitle.is-6 a')->text())
            : null;
        $date = null;
        if ($dateText !== null) {
            try {
                $date = Carbon::parse($dateText);
            } catch (\Exception) {
                $date = null;
            }
        }

        $description = $this->node->filter('div.level.has-text-grey-dark')->count()
            ? trim($this->node->filter('div.level.has-text-grey-dark')->text())
            : null;

        $tags = $this->node->filter('.tags a.tag')->count()
            ? $this->node->filter('.tags a.tag')->each(function (Crawler $tag) {
                return trim($tag->text());
            })
            : [];

        $downloadNode = $this->node->filter('a.button.is-primary.is-fullwidth');
        $download = null;
        $size = null;

        if ($downloadNode->count() > 0) {
            $torrentNode = $downloadNode->reduce(function (Crawler $node) {
                return str_contains($node->attr('title') ?? '', 'Download .torrent');
            });

            $targetNode = $torrentNode->count() > 0 ? $torrentNode : $downloadNode->first();
            $download = $targetNode->attr('href');
            $size = $this->extractSizeFromText($targetNode->text());
        }

        $code = $this->extractCode($title, $description, $link);
        $itemId = CodeNormalizer::compactIdFromCode($code);

        $item = new Item(
            id: $itemId,
            title: $title,
            url: $link,
            image: $cover,
            date: $date,
            code: $code,
            tags: collect($tags),
            size: $size,
            description: $description,
            actresses: collect(),
            download: $download
        );

        ItemParsed::dispatch($item, 'ffjav');

        return $item;
    }

    private function extractSizeFromText(string $text): ?float
    {
        $text = strtoupper(trim(preg_replace('/\s+/', ' ', $text) ?? ''));

        if (preg_match('/(\d+(?:\.\d+)?)\s*(GB|MB|KB)\b/', $text, $matches)) {
            $value = (float) $matches[1];
            $unit = $matches[2];

            return match ($unit) {
                'GB' => $value,
                'MB' => $value / 1024,
                'KB' => $value / (1024 * 1024),
                default => null,
            };
        }

        return null;
    }

    private function extractCode(?string $title, ?string $description, ?string $link): ?string
    {
        $candidates = array_filter([$title, $description, $this->extractSlug($link)]);

        foreach ($candidates as $candidate) {
            $candidate = strtoupper(trim($candidate));

            if (preg_match('/\b(FC2(?:-?PPV)?-?\d+[A-Z0-9]*)\b/i', $candidate, $matches)) {
                return CodeNormalizer::normalize($matches[1]);
            }

            if (preg_match('/\b([A-Z]{2,}(?:-[A-Z0-9]+)*-?\d+[A-Z0-9]*)\b/', $candidate, $matches)) {
                return CodeNormalizer::normalize($matches[1]);
            }
        }

        return null;
    }

    private function extractSlug(?string $link): ?string
    {
        if ($link === null || $link === '') {
            return null;
        }

        $path = parse_url($link, PHP_URL_PATH);
        if (! is_string($path)) {
            return null;
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        if ($segments === []) {
            return null;
        }

        return urldecode((string) end($segments));
    }
}
