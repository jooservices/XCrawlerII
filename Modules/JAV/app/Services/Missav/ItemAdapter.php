<?php

namespace Modules\JAV\Services\Missav;

use Carbon\Carbon;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Support\CodeNormalizer;
use Symfony\Component\DomCrawler\Crawler;

class ItemAdapter
{
    public function __construct(protected Crawler $node) {}

    /**
     * @return array<string, mixed>
     */
    public function getDetailMeta(): array
    {
        return [
            'release_date' => $this->extractReleaseDate(),
            'actresses' => $this->extractDetailAnchors('Actress'),
            'genres' => $this->extractDetailAnchors('Genre'),
            'series' => $this->extractDetailAnchors('Series'),
            'maker' => $this->extractDetailAnchors('Maker'),
            'studio' => $this->extractDetailAnchors('Studio'),
            'producer' => $this->extractDetailAnchors('Producer'),
            'director' => $this->extractDetailAnchors('Director'),
            'label' => $this->extractDetailAnchors('Label'),
            'tag' => $this->extractDetailAnchors('Tag'),
        ];
    }

    public function getItem(): Item
    {
        $item = $this->isDetailPage()
            ? $this->parseDetail()
            : $this->parseList();

        ItemParsed::dispatch($item, 'missav');

        return $item;
    }

    private function isDetailPage(): bool
    {
        return $this->node->filter('meta[property="og:video:release_date"]')->count() > 0;
    }

    private function parseList(): Item
    {
        $linkNode = $this->node->filter('a[href]')->first();
        $link = $linkNode->count() ? $linkNode->attr('href') : null;

        $titleNode = $this->node->filter('div.my-2.text-sm.text-nord4.truncate a');
        $title = $titleNode->count() ? trim($titleNode->text()) : ($linkNode->count() ? trim($linkNode->text()) : null);

        $imageNode = $this->node->filter('img');
        $image = null;
        if ($imageNode->count()) {
            $image = $imageNode->attr('data-src') ?: $imageNode->attr('src');
        }

        $slug = $this->extractSlug($link);
        $code = $this->normalizeCode($slug, $title, null);
        $id = $slug ?: CodeNormalizer::compactIdFromCode($code);

        return new Item(
            id: $id,
            title: $title,
            url: $link,
            image: $image,
            date: null,
            code: $code,
            tags: collect(),
            size: null,
            description: null,
            actresses: collect(),
            download: null,
            genres: collect(),
            series: collect(),
            maker: collect(),
            studio: collect(),
            producer: collect(),
            director: collect(),
            label: collect(),
            tag: collect()
        );
    }

    private function parseDetail(): Item
    {
        $url = $this->extractMetaContent('meta[property="og:url"]')
            ?? $this->extractLinkHref('link[rel="canonical"]');

        $image = $this->extractMetaContent('meta[property="og:image"]');

        $description = $this->extractDescription()
            ?? $this->extractMetaContent('meta[name="description"]');

        $releaseDate = $this->extractReleaseDate();

        $detailTitle = $this->extractDetailText('Title');
        $pageTitle = $this->extractText('h1.text-base')
            ?? $this->extractMetaContent('meta[property="og:title"]');
        $title = $detailTitle ?: $pageTitle;

        $code = $this->normalizeCode(
            $this->extractDetailText('Code'),
            $title,
            $url
        );
        $id = CodeNormalizer::compactIdFromCode($code) ?? $this->extractSlug($url);

        $actresses = $this->extractDetailAnchors('Actress');
        if ($actresses === []) {
            $actresses = $this->extractMetaActors();
        }

        $genres = $this->extractDetailAnchors('Genre');
        $series = $this->extractDetailAnchors('Series');
        $maker = $this->extractDetailAnchors('Maker');
        $studio = $this->extractDetailAnchors('Studio');
        $producer = $this->extractDetailAnchors('Producer');
        $director = $this->extractDetailAnchors('Director');
        $label = $this->extractDetailAnchors('Label');
        $tag = $this->extractDetailAnchors('Tag');

        $tags = array_values(array_unique(array_filter(array_merge(
            $genres,
            $series,
            $maker,
            $studio,
            $producer,
            $director,
            $label,
            $tag
        ))));

        $streamUrl = $this->extractStreamUrl();
        $downloadUrl = $this->extractDownloadLink();

        return new Item(
            id: $id,
            title: $title,
            url: $url,
            image: $image,
            date: $releaseDate,
            code: $code,
            tags: collect(),
            size: null,
            description: $description,
            actresses: collect($actresses),
            download: $streamUrl ?? $downloadUrl,
            genres: collect($genres),
            series: collect($series),
            maker: collect($maker),
            studio: collect($studio),
            producer: collect($producer),
            director: collect($director),
            label: collect($label),
            tag: collect($tag)
        );
    }

    private function extractReleaseDate(): ?Carbon
    {
        foreach (['Release date', 'Release Date'] as $label) {
            $row = $this->findDetailRow($label);
            if ($row === null) {
                continue;
            }

            $timeNode = $row->filter('time');
            $value = $timeNode->count()
                ? trim($timeNode->text())
                : $this->extractRowValueText($row);

            if ($value === null) {
                continue;
            }

            try {
                return Carbon::parse($value);
            } catch (\Exception) {
                return null;
            }
        }

        $meta = $this->extractMetaContent('meta[property="og:video:release_date"]');
        if ($meta !== null) {
            try {
                return Carbon::parse($meta);
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }

    private function extractDescription(): ?string
    {
        $descriptionNode = $this->node->filter('div.text-secondary.break-all');
        if (! $descriptionNode->count()) {
            return null;
        }

        $text = trim($descriptionNode->first()->text());

        return $text !== '' ? $text : null;
    }

    private function extractDetailAnchors(string $label): array
    {
        $row = $this->findDetailRow($label);
        if ($row === null) {
            return [];
        }

        $anchors = $row->filter('a');
        if (! $anchors->count()) {
            $value = $this->extractRowValueText($row);

            return $value !== null ? [$value] : [];
        }

        return $anchors->each(function (Crawler $anchor) {
            return trim($anchor->text());
        });
    }

    private function extractDetailText(string $label): ?string
    {
        $row = $this->findDetailRow($label);
        if ($row === null) {
            return null;
        }

        return $this->extractRowValueText($row);
    }

    private function extractRowValueText(Crawler $row): ?string
    {
        $spanNodes = $row->filter('span');
        if (! $spanNodes->count()) {
            return null;
        }

        $value = trim($spanNodes->last()->text());

        return $value !== '' ? $value : null;
    }

    private function findDetailRow(string $label): ?Crawler
    {
        $rows = $this->node->filter('div.text-secondary');

        foreach ($rows as $row) {
            $crawler = new Crawler($row);
            $labelNode = $crawler->filter('span');
            if (! $labelNode->count()) {
                continue;
            }

            $labelText = strtolower(trim($labelNode->first()->text()));
            $labelText = rtrim($labelText, ':');

            if ($labelText === strtolower($label)) {
                return $crawler;
            }
        }

        return null;
    }

    private function extractStreamUrl(): ?string
    {
        $scripts = $this->node->filter('script');
        foreach ($scripts as $script) {
            $crawler = new Crawler($script);
            $content = $crawler->text('', true);
            if ($content === '') {
                continue;
            }

            if (preg_match('/https?:\\/\\/[^\"\']+\.m3u8/i', $content, $matches)) {
                return $matches[0];
            }
        }

        return null;
    }

    private function extractDownloadLink(): ?string
    {
        $links = $this->node->filter('a');
        foreach ($links as $link) {
            $crawler = new Crawler($link);
            $text = trim($crawler->text());
            if (strcasecmp($text, 'Download') !== 0) {
                continue;
            }

            $href = $crawler->attr('href');
            if ($href !== null && $href !== '') {
                return $href;
            }
        }

        return null;
    }

    private function extractMetaActors(): array
    {
        $actors = $this->node->filter('meta[property="og:video:actor"]');
        if (! $actors->count()) {
            return [];
        }

        return $actors->each(function (Crawler $actor) {
            return trim((string) $actor->attr('content'));
        });
    }

    private function extractMetaContent(string $selector): ?string
    {
        $node = $this->node->filter($selector);
        if (! $node->count()) {
            return null;
        }

        $content = $node->attr('content');

        return $content !== null && $content !== '' ? $content : null;
    }

    private function extractLinkHref(string $selector): ?string
    {
        $node = $this->node->filter($selector);
        if (! $node->count()) {
            return null;
        }

        $href = $node->attr('href');

        return $href !== null && $href !== '' ? $href : null;
    }

    private function extractText(string $selector): ?string
    {
        $node = $this->node->filter($selector);
        if (! $node->count()) {
            return null;
        }

        $text = trim($node->first()->text());

        return $text !== '' ? $text : null;
    }

    private function extractSlug(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path)) {
            return null;
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        if ($segments === []) {
            return null;
        }

        return strtolower(urldecode((string) end($segments)));
    }

    private function normalizeCode(?string $value, ?string $title, ?string $url): ?string
    {
        $candidates = array_filter([$value, $title, $this->extractSlug($url)]);

        foreach ($candidates as $candidate) {
            if (preg_match('/\b([A-Z]{2,}[0-9]{2,}(?:-[0-9A-Z]+)*)\b/i', $candidate, $matches)) {
                return CodeNormalizer::normalize($matches[1]);
            }
        }

        return CodeNormalizer::normalize($value ?? $this->extractSlug($url));
    }
}
