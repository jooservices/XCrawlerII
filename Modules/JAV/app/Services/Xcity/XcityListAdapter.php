<?php

namespace Modules\JAV\Services\Xcity;

use Illuminate\Support\Collection;
use Modules\JAV\Dtos\XcityIdol;
use Modules\JAV\Dtos\XcityIdolPage;
use Symfony\Component\DomCrawler\Crawler;

class XcityListAdapter
{
    private const BASE_URL = 'https://xxx.xcity.jp';

    private const IDOL_BASE_PATH = '/idol/';

    public function __construct(
        private readonly Crawler $dom
    ) {}

    public function page(): XcityIdolPage
    {
        $idols = $this->idols();

        return new XcityIdolPage(
            idols: $idols,
            nextUrl: $this->nextUrl()
        );
    }

    /**
     * @return Collection<int, XcityIdol>
     */
    public function idols(): Collection
    {
        $rows = [];

        $this->dom->filter('a[href*="detail/"], a[href*="/idol/detail/"]')->each(function (Crawler $node) use (&$rows) {
            $href = trim((string) $node->attr('href'));
            if ($href === '' || ! preg_match('#(?:/idol/)?detail/(\d+)/?#', $href, $matches)) {
                return;
            }

            $xcityId = $matches[1];
            $name = trim($node->text(''));

            if ($name === '' && $node->filter('img')->count() > 0) {
                $name = trim((string) $node->filter('img')->first()->attr('alt'));
            }

            if ($name === '') {
                return;
            }

            $cover = null;
            if ($node->filter('img')->count() > 0) {
                $cover = $this->toAbsoluteUrl((string) $node->filter('img')->first()->attr('src'));
            }

            $detailUrl = $this->toAbsoluteUrl($href);

            if (! isset($rows[$xcityId])) {
                $rows[$xcityId] = new XcityIdol(
                    xcityId: $xcityId,
                    name: $name,
                    detailUrl: $detailUrl,
                    coverImage: $cover
                );

                return;
            }

            // Prefer richer record if this duplicate has cover and old one doesn't.
            $previous = $rows[$xcityId];
            if ($previous->coverImage === null && $cover !== null) {
                $rows[$xcityId] = new XcityIdol(
                    xcityId: $xcityId,
                    name: $previous->name,
                    detailUrl: $previous->detailUrl,
                    coverImage: $cover
                );
            }
        });

        return collect(array_values($rows));
    }

    public function nextUrl(): ?string
    {
        $next = $this->dom->filter('a[rel="next"]');
        if ($next->count() > 0) {
            return $this->toAbsoluteUrl((string) $next->first()->attr('href'));
        }

        $candidates = $this->dom->filter('a[href]')->reduce(function (Crawler $node): bool {
            $text = mb_strtolower(trim($node->text('')));

            return $text === 'next' || str_contains($text, '次');
        });

        if ($candidates->count() > 0) {
            return $this->toAbsoluteUrl((string) $candidates->first()->attr('href'));
        }

        return null;
    }

    private function toAbsoluteUrl(string $url): string
    {
        if ($url === '') {
            return $url;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        if (str_starts_with($url, '/')) {
            return self::BASE_URL.$url;
        }

        if (str_starts_with($url, 'detail/')) {
            return self::BASE_URL.self::IDOL_BASE_PATH.ltrim($url, '/');
        }

        return self::BASE_URL.'/'.ltrim($url, '/');
    }
}
