<?php

namespace Modules\JAV\Services\Missav;
use Modules\JAV\Contracts\IItems;
use Modules\JAV\Dtos\Items;
use Symfony\Component\DomCrawler\Crawler;

class ItemsAdapter implements IItems
{
    private Crawler $dom;

    private Items $items;

    public function __construct(private readonly ?string $html)
    {
        $this->dom = new Crawler($this->html ?? '');
    }

    public function hasNextPage(): bool
    {
        return $this->dom->filter('a[rel="next"]')->count() > 0;
    }

    public function nextPage(): int
    {
        $nextLink = $this->dom->filter('a[rel="next"]');
        if (! $nextLink->count()) {
            return 1;
        }

        $href = $nextLink->attr('href');
        $page = $this->extractPageNumber($href);

        return $page ?? ($this->currentPage() + 1);
    }

    public function currentPage(): int
    {
        $currentPageNode = $this->dom->filter('span[aria-current="page"] span');
        if ($currentPageNode->count()) {
            return (int) trim($currentPageNode->text());
        }

        $inputNode = $this->dom->filter('input[name="page"]');
        if ($inputNode->count()) {
            $value = $inputNode->attr('value');

            return $value !== null ? (int) trim($value) : 1;
        }

        return 1;
    }

    public function items(): Items
    {
        if (isset($this->items)) {
            return $this->items;
        }

        if (! $this->dom->count()) {
            return $this->items = new Items(
                items: collect(),
                hasNextPage: false,
                nextPage: 1
            );
        }

        $grid = $this->dom->filter('div.grid.grid-cols-2');
        $scope = $grid->count() ? $grid->first() : $this->dom;

        $items = $scope->filter('div.thumbnail.group')->each(function (Crawler $node) {
            return (new ItemAdapter($node))->getItem();
        });

        return $this->items = new Items(
            items: collect($items),
            hasNextPage: $this->hasNextPage(),
            nextPage: $this->nextPage()
        );
    }

    private function extractPageNumber(?string $href): ?int
    {
        if ($href === null || $href === '') {
            return null;
        }

        $query = parse_url($href, PHP_URL_QUERY);
        if (! is_string($query)) {
            return null;
        }

        parse_str($query, $params);
        if (! isset($params['page'])) {
            return null;
        }

        return (int) $params['page'];
    }
}
