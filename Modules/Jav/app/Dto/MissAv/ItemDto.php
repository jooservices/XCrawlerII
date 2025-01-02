<?php

namespace Modules\Jav\Dto\MissAv;

use Modules\Jav\Dto\BaseDto;
use stdClass;

class ItemDto extends BaseDto
{
    final public function transform(mixed $response): static
    {
        $this->data = new stdClass();

        $this->data->cover = $response->filter('img')->attr('data-src');
        $this->data->preview = $response->filter('video')->attr('data-src');
        $this->data->url = $response->filter('a')->attr('href');
        $this->data->title = $response->filter('.truncate a')->text();

        return $this;
    }
}
