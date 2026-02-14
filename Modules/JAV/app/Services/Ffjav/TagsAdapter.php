<?php

namespace Modules\JAV\Services\Ffjav;

use JOOservices\Client\Response\ResponseWrapper;
use Symfony\Component\DomCrawler\Crawler;

class TagsAdapter
{
    private Crawler $dom;

    public function __construct(
        private readonly ResponseWrapper $response
    ) {
        $this->dom = new Crawler($this->response->toPsrResponse()->getBody()->getContents());
    }

    public function tags(): \Illuminate\Support\Collection
    {
        return collect($this->dom->filter('.column.is-3 a.button.is-link.is-outlined.is-fullwidth')->each(function (Crawler $node) {
            return (new TagAdapter($node))->value();
        }))
            ->filter()
            ->unique()
            ->values();
    }
}
