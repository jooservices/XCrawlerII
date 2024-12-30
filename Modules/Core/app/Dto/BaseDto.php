<?php

namespace Modules\Core\Dto;

use JsonException;
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

    /**
     * @throws JsonException
     */
    public function transform(mixed $response): ?static
    {
        if ($response === null) {
            throw new InvalidDtoDataException('Response is null');
        }

        switch (true) {
            case $response instanceof IResponse:
                $this->loadDataFromResponse($response);
                break;
            case is_array($response):
                $this->loadDataFromArray($response);
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

    private function loadDataFromResponse(IResponse $response): void
    {
        if (!$response->isSuccess()) {
            throw new InvalidDtoDataException('Response is not successful');
        }

        $this->data = $response->parseBody()->getData();
    }

    private function loadDataFromArray(array $response): void
    {
        $this->data = json_decode(
            json_encode($response, JSON_THROW_ON_ERROR),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
