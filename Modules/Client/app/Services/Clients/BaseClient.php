<?php

namespace Modules\Client\Services\Clients;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Client\Interfaces\IClient;
use Modules\Client\Interfaces\IResponse;
use Modules\Client\Services\Clients\Responses\BaseResponse;
use Modules\Client\Services\Factory;
use Modules\Client\Services\RequestLogService;

class BaseClient implements IClient
{
    protected ClientInterface $client;

    protected string $contentType = 'x-www-form-urlencoded';

    public function __construct()
    {
        $this->client = app(Factory::class)
            ->enableRetries()
            ->make();
    }

    public function get(
        string $endpoint,
        array $payload = [],
        array $options = []
    ): IResponse {
        return $this->request(__FUNCTION__, $endpoint, $payload, $options);
    }

    public function post(
        string $endpoint,
        array $payload = [],
        array $options = []
    ): IResponse {
        return $this->request(__FUNCTION__, $endpoint, $payload, $options);
    }

    public function put(
        string $endpoint,
        array $payload = [],
        array $options = []
    ): IResponse {
        return $this->request(__FUNCTION__, $endpoint, $payload, $options);
    }

    public function delete(
        string $endpoint,
        array $payload = [],
        array $options = []
    ): IResponse {
        return $this->request(__FUNCTION__, $endpoint, $payload, $options);
    }

    public function patch(
        string $endpoint,
        array $payload = [],
        array $options = []
    ): IResponse {
        return $this->request(__FUNCTION__, $endpoint, $payload, $options);
    }

    public function request(
        string $method,
        string $endpoint,
        array $payload = [],
        array $options = []
    ): IResponse {
        $key = md5(
            Str::lower($method . $endpoint)
            . serialize($payload)
            . serialize($options)
        );

        return Cache::remember(
            $key,
            config('jav.onejav.cache_interval'),
            function () use ($method, $endpoint, $payload, $options) {
                $method = Str::upper($method);
                $logService = app(RequestLogService::class);
                $responseClass = $this->getResponseClass();

                try {
                    $payload = $this->convertToUTF8($payload);

                    if ($method == 'GET') {
                        $options['query'] = $payload;
                    } else {
                        switch ($this->contentType) {
                            case 'application/x-www-form-urlencoded':
                                $options['form_params'] = $payload;
                                break;
                            case 'json':
                            default:
                                $options['json'] = $payload;
                                break;
                        }
                    }

                    $logService->request(
                        $method,
                        $endpoint,
                        $options
                    );

                    return new $responseClass(
                        $this->client->request($method, $endpoint, $options)
                    );
                } catch (BadResponseException $exception) {
                    if ($exception->hasResponse()) {
                        return new $responseClass($exception->getResponse());
                    }
                } catch (\Exception | GuzzleException $exception) {
                    $logService->exception($exception);

                    return new $responseClass();
                }
            }
        );
    }

    protected function getResponseClass(): string
    {
        return BaseResponse::class;
    }

    protected function convertToUTF8(array $array): array
    {
        array_walk_recursive($array, function (&$item) {
            if (!mb_detect_encoding($item, 'utf-8', true)) {
                $item = mb_convert_encoding($item, 'UTF-8', 'ISO-8859-1');
            }
        });

        return $array;
    }
}
