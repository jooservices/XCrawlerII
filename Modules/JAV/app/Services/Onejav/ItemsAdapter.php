<?php

namespace Modules\JAV\Services\Onejav;

use JOOservices\Client\Response\ResponseWrapper;
use Modules\JAV\Contracts\IItems;
use Modules\JAV\Dtos\Items;
use Symfony\Component\DomCrawler\Crawler;

class ItemsAdapter implements IItems
{
    private Crawler $dom;

    public function __construct(private readonly ?ResponseWrapper $response)
    {
        $this->dom = new Crawler($this->response->toPsrResponse()->getBody()->getContents());
    }
    public function hasNextPage(): bool
    {
        $lastPageNode = $this->dom->filter('.pagination-list li')->last();
        $lastPage = $lastPageNode->count() ? (int) $lastPageNode->text() : 1;

        return $this->currentPage() < $lastPage;
    }

    public function nextPage(): int
    {
        return $this->hasNextPage() ? $this->currentPage() + 1 : 1;
    }

    public function currentPage(): int
    {
        $currentPageNode = $this->dom->filter('.pagination-list li a.pagination-link.button.is-primary:not(.is-inverted)');

        return $currentPageNode->count() ? (int) $currentPageNode->text() : 1;
    }

    public function items(): Items
    {
        $items = $this->dom->filter('.card.mb-3 .columns')->each(function (Crawler $node) {
            return (new ItemAdapter($node))->getItem();
        });

        return new Items(
            items: collect($items),
            hasNextPage: $this->hasNextPage(),
            nextPage: $this->nextPage()
        );
    }


}
