<?php

declare(strict_types=1);

namespace Modules\JAV\Services\Crawling\Crawlers\Onejav;

use Modules\Core\DTOs\ListDto;
use Modules\JAV\Contracts\Crawling\CrawlingMovieInterface;
use Modules\JAV\DTOs\MovieDto;
use Modules\JAV\Enums\SourceEnum;
use Modules\JAV\Services\Crawling\Client\OnejavClient;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

final class Crawler implements CrawlingMovieInterface
{
    public function __construct(
        private readonly OnejavClient $client,
    ) {
    }

    public function new(?int $page = null): ListDto
    {
        $page = $page ?? 1;
        $path = '/new' . ($page > 1 ? '?page=' . $page : '');

        return new Items($this->client->get($path))
            ->toListDto();
    }

    public function popular(?int $page = null): ListDto
    {
        $page = $page ?? 1;
        $path = '/popular/' . ($page > 1 ? '?page=' . $page : '');

        return (new Items($this->client->get($path)))->toListDto();
    }

    public function daily(?\DateTimeInterface $date = null, ?int $page = null): ListDto
    {
        $date = $date ?? new \DateTimeImmutable();
        $page = $page ?? 1;
        $dateStr = $date->format('Y/m/d');
        $path = '/' . $dateStr . ($page > 1 ? '?page=' . $page : '');

        return (new Items($this->client->get($path)))->toListDto();
    }

    public function item(string $codeOrUrl): MovieDto
    {
        if (str_starts_with($codeOrUrl, 'http')) {
            $path = (string) parse_url($codeOrUrl, PHP_URL_PATH);
        } else {
            $path = str_starts_with($codeOrUrl, '/') ? $codeOrUrl : '/torrent/' . $codeOrUrl;
        }

        $html = $this->client->get($path)->toPsrResponse()->getBody()->getContents();
        $dom = new DomCrawler($html);

        $node = $dom->filter('.card.mb-3 .columns')->first();
        if ($node->count() === 0) {
            $node = $dom->filter('body');
        }

        return (new Item($node, SourceEnum::Onejav))->toMovie();
    }
}
