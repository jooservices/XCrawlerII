<?php

namespace Modules\JAV\Services\Onejav;

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
        // 1. Cover
        // List page: .column .image
        // Single page (Onejav): .column .image (might be similar)
        // We need to robustly find these. The selectors below are based on NewAdapter.
        $cover = $this->node->filter('.column .image')->count() ? $this->node->filter('.column .image')->attr('src') : null;

        // 2. Title & Link
        $titleNode = $this->node->filter('.column.is-5 .card-content h5.title a');
        if ($titleNode->count()) {
            $title = $titleNode->text();
            $link = $titleNode->attr('href');
        } else {
            $title = null;
            $link = null;
        }

        // 3. Size
        $size = $this->node->filter('.column.is-5 .card-content h5 span.is-size-6.has-text-grey')->count()
            ? $this->node->filter('.column.is-5 .card-content h5 span.is-size-6.has-text-grey')->text()
            : null;

        if ($size) {
            $size = $this->convertSize($size);
        }

        // 4. Date
        $date = $this->node->filter('.subtitle.is-6 a')->count()
            ? $this->node->filter('.subtitle.is-6 a')->attr('href')
            : null;

        if ($date) {
            // Handle both /2026/02/12 and /date/2026/02/12
            // Remove 'date/' prefix and leading slashes
            $dateStr = str_replace('date/', '', trim($date, '/'));
            try {
                $date = Carbon::createFromFormat('Y/m/d', $dateStr);
            } catch (\Exception $e) {
                $date = null;
            }
        }

        // 5. Description
        $description = $this->node->filter('p.level.has-text-grey-dark')->count()
            ? $this->node->filter('p.level.has-text-grey-dark')->text()
            : null;

        // 6. Tags
        $tags = $this->node->filter('.tags a.tag')->count()
            ? $this->node->filter('.tags a.tag')->each(function (Crawler $tag) {
                return $tag->text();
            })
            : [];

        // 7. Actresses
        $actresses = $this->node->filter('.panel .panel-block')->count()
            ? $this->node->filter('.panel .panel-block')->each(function (Crawler $actress) {
                return $actress->text();
            })
            : [];

        // 8. ID & Code
        $id = null;
        $code = null;
        if ($link) {
            $parts = explode('/', trim($link, '/'));
            $id = end($parts);
            $code = CodeNormalizer::normalize($id);
        }

        // 9. Download
        // Onejav: a.button.is-primary.is-fullwidth
        // 141jav: a.button.is-primary.is-fullwidth (search for "Download .torrent")
        $downloadNode = $this->node->filter('a.button.is-primary.is-fullwidth');
        $download = null;

        if ($downloadNode->count() > 0) {
            // Check for specific torrent button first (141jav style)
            $torrentNode = $downloadNode->reduce(function (Crawler $node) {
                return str_contains($node->attr('title') ?? '', 'Download .torrent');
            });

            if ($torrentNode->count() > 0) {
                $download = $torrentNode->attr('href');
            } else {
                // Fallback to first button (Onejav style)
                $download = $downloadNode->first()->attr('href');
            }
        }

        $item = new Item(
            id: $id,
            title: $title,
            url: $link,
            image: $cover,
            date: $date,
            code: $code,
            tags: collect($tags),
            size: $size,
            description: $description,
            actresses: collect($actresses),
            download: $download
        );

        ItemParsed::dispatch($item, 'onejav');

        return $item;
    }

    private function convertSize(?string $size): ?float
    {
        if (! $size) {
            return null;
        }

        $size = trim($size);
        if (preg_match('/^(\d+(\.\d+)?)\s*(GB|MB|KB)$/i', $size, $matches)) {
            $value = (float) $matches[1];
            $unit = strtoupper($matches[3]);

            return match ($unit) {
                'GB' => $value,
                'MB' => $value / 1024,
                'KB' => $value / (1024 * 1024),
                default => null,
            };
        }

        return null;
    }
}
