<?php

namespace Modules\Client\Services\Clients\ResponseData;

use Symfony\Component\DomCrawler\Crawler;

class DomResponseData extends BaseResponseData
{
    protected function parseResponse(): Crawler
    {
        return new Crawler($this->getBody());
    }
}
