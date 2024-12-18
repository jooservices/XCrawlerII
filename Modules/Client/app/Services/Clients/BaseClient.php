<?php

namespace Modules\Client\Services\Clients;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Client\Events\PrepareRequestOptionsCompletedEvent;
use Modules\Client\Events\PrepareRequestOptionsEvent;
use Modules\Client\Events\RequestCompletedEvent;
use Modules\Client\Events\RequestWithoutCachedEvent;
use Modules\Client\Helpers\StringHelper;
use Modules\Client\Interfaces\IClient;
use Modules\Client\Interfaces\IResponse;
use Modules\Client\Services\Clients\Responses\BaseResponse;
use Modules\Client\Services\Clients\Traits\THasRequests;
use Modules\Client\Services\Factory;
use Modules\Client\Services\RequestLogService;
use Modules\Core\Helpers\KeyHelper;

class BaseClient implements IClient
{
    use THasRequests;

    protected ClientInterface $client;

    protected string $contentType = 'x-www-form-urlencoded';

    /**
     * @throws BindingResolutionException
     */
    public function __construct(array $options = [])
    {
        $this->client = app(Factory::class)
            ->enableRetries()
            ->make($options);
    }

    public function request(
        string $method,
        string $endpoint,
        array $payload = [],
        array $options = []
    ): IResponse {
        $cOptions = $options;
        unset($cOptions['headers']['User-Agent']);
        $key = KeyHelper::generateKey(
            __FUNCTION__,
            Str::lower($method . $endpoint),
            $payload,
            $cOptions
        );

        if (config('client.cache.enable')) {
            return Cache::remember(
                $key,
                config('client.cache.interval', 60),
                function () use ($method, $endpoint, $payload, $options) {
                    RequestWithoutCachedEvent::dispatch();

                    return $this->clientRequest(
                        $method,
                        $endpoint,
                        $payload,
                        $options
                    );
                }
            );
        }

        return $this->clientRequest(
            $method,
            $endpoint,
            $payload,
            $options
        );
    }

    private function clientRequest(
        string $method,
        string $endpoint,
        array $payload,
        array $options
    ): IResponse {
        $method = Str::upper($method);
        $logService = app(RequestLogService::class);
        $responseClass = $this->getResponseClass();

        try {
            PrepareRequestOptionsEvent::dispatch();

            $payload = StringHelper::convertToUTF8a($payload);

            if ($method === 'GET') {
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

            PrepareRequestOptionsCompletedEvent::dispatch();

            $logService->request(
                $method,
                $endpoint,
                $options
            );

            RequestCompletedEvent::dispatch();

            return new $responseClass(
                $this->client->request($method, $endpoint, $options)
            );
        } catch (BadResponseException $exception) {
            if ($exception->hasResponse()) {
                return new $responseClass($exception->getResponse());
            }
        }

        return new $responseClass();
    }

    protected function getResponseClass(): string
    {
        return BaseResponse::class;
    }
}
