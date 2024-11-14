<?php

namespace Modules\Client\Services\Clients\ResponseData;

class JsonResponseData extends BaseResponseData
{
    protected function parseResponse(): mixed
    {
        return json_decode($this->getBody());
    }
}
