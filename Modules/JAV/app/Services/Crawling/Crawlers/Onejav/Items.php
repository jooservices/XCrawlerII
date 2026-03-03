<?php

declare(strict_types=1);

namespace Modules\JAV\Services\Crawling\Crawlers\Onejav;

use JOOservices\Client\Contracts\ResponseWrapperInterface;
use Modules\Core\DTOs\ListDto;
use Modules\Core\DTOs\PaginationDto;
use Modules\JAV\Enums\SourceEnum;
use Symfony\Component\DomCrawler\Crawler;

final class Items
{
    public function __construct(
        private readonly ResponseWrapperInterface $response,
    ) {
    }

    public function toListDto(): ListDto
    {
        $html = $this->response->toPsrResponse()->getBody()->getContents();
        $dom = new Crawler($html);

        $itemNodes = $dom->filter('.card.mb-3 .columns');
        $items = $itemNodes->each(
            fn (Crawler $node) => new Item($node, SourceEnum::Onejav)->toMovie()
        );

        $current = $this->currentPage($dom);
        $hasNext = $this->hasNextPage($dom);
        $next = $hasNext ? $current + 1 : null;
        $count = count($items);
        $perPage = max(1, $count);

        $pagination = new PaginationDto($current, $perPage, $hasNext, $next);

        return new ListDto(collect($items), $pagination);
    }

    private function currentPage(Crawler $dom): int
    {
        $currentLink = $dom->filter('.pagination-list li a.pagination-link.button.is-primary:not(.is-inverted)')->first();
        if ($currentLink->count() === 0) {
            return 1;
        }

        $text = trim($currentLink->text() ?? '');
        $page = (int) $text;

        return $page >= 1 ? $page : 1;
    }

    private function hasNextPage(Crawler $dom): bool
    {
        $nextLink = $dom->filter('.pagination-next')->first();
        if ($nextLink->count() === 0) {
            return false;
        }

        $href = $nextLink->attr('href');

        return $href !== null && $href !== '';
    }
}
