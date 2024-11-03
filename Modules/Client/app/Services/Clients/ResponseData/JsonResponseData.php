<?php

namespace Modules\Client\Services\Clients\ResponseData;

use Symfony\Component\DomCrawler\Crawler;

class JsonResponseData extends BaseResponseData
{
    protected function parseResponse(): mixed
    {
        return json_decode($this->getBody());
    }
}
