<?php

namespace Modules\JAV\Services\Onejav;

use Symfony\Component\DomCrawler\Crawler;

class TagAdapter
{
    public function __construct(
        private readonly Crawler $node
    ) {}

    public function value(): ?string
    {
        $text = trim($this->node->text());

        return $text === '' ? null : $text;
    }
}
