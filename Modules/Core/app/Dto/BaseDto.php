<?php

namespace Modules\Core\Dto;

use Modules\Client\Interfaces\IResponse;
use Modules\Core\Dto\Interfaces\IDto;
use Modules\Core\Dto\Traits\TCastsDto;
use Modules\Core\Dto\Traits\THasProperties;
use Modules\Core\Exceptions\InvalidDtoDataException;
use stdClass;

class BaseDto implements IDto
{
    use TCastsDto;
    use THasProperties;

    protected ?stdClass $data;

    public function transform(mixed $response): ?static
    {
        if ($response === null) {
            throw new InvalidDtoDataException('Response is null');
        }

        switch (true) {
            case $response instanceof IResponse:
                if (!$response->isSuccess()) {
                    throw new InvalidDtoDataException('Response is not successful');
                }

                $this->data = $response->parseBody()->getData();
                break;
            case is_array($response):
                $this->data = json_decode(json_encode($response), false);
                break;
            case is_object($response):
                $this->data = $response;
                break;
            default:
                return $this->data = $response;
        }

        /**
         * @TODO Validate data
         */

        return $this;
    }
}
