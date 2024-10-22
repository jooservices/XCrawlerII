<?php

namespace Modules\Client\Services;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Factory
{
    protected Client $client;

    protected HandlerStack|MockHandler|null $handler;

    protected array $mockingResponse = [];

    protected array $histories = [];

    public const int MAX_RETRIES = 3;

    public const int DELAY_IN_SECONDS = 1;

    public const int MIN_ERROR_CODE = 400;

    /**
     * @throws BindingResolutionException
     */
    public function make(array $options = []): Client
    {
        if (!isset($options['handler'])) {
            $options['handler'] = $this->getHandler();
        }

        $this->client = app()
            ->makeWith(Client::class, ['config' => $options]);

        return $this->client;
    }

    protected function getHandler(bool $useMock = false): HandlerStack|MockHandler
    {
        if (
            $useMock || $this->hasMocking()
        ) {
            $mockHandler = new MockHandler();
            foreach ($this->mockingResponse as $mocking) {
                $mockHandler->append($mocking);
            }
        }

        $this->handler = $this->handler ?? HandlerStack::create($mockHandler ?? null);

        return $this->handler;
    }

    /**
     * @return $this
     */
    public function pushMiddleware(callable $middleware, string $name = ''): static
    {
        $this->getHandler()->push($middleware, $name);

        return $this;
    }

    /**
     * @return $this
     */
    public function enableRetries(
        int $maxRetries = self::MAX_RETRIES,
        int $delayInSec = self::DELAY_IN_SECONDS,
        int $minErrorCode = self::MIN_ERROR_CODE
    ): static {
        $decider = function (
            int $retries,
            RequestInterface $request,
            ?ResponseInterface $response = null
        ) use (
            $maxRetries,
            $minErrorCode
        ): bool {
            return
                $retries < $maxRetries
                && $response !== null
                && $response->getStatusCode() >= $minErrorCode;
        };

        $this->pushMiddleware(
            Middleware::retry(
                $decider,
                function (int $retries, ResponseInterface $response) use ($delayInSec): float|int {
                    if ($this->hasMocking()) {
                        return 1;
                    }

                    if (!$response->hasHeader('Retry-After')) {
                        return $retries * $delayInSec * 1000;
                    }

                    $retryAfter = $response->getHeaderLine('Retry-After');

                    if (!is_numeric($retryAfter)) {
                        $retryAfter = (new DateTime($retryAfter))->getTimestamp() - time();
                    }

                    return (int) $retryAfter * 1000;
                }
            )
        );

        return $this;
    }

    public function enableLogging(
        LoggerInterface $logger,
        string $format = MessageFormatter::DEBUG,
        string $level = LogLevel::INFO
    ): self {
        return $this->pushMiddleware(
            Middleware::log($logger, new MessageFormatter($format), $level),
            'log'
        );
    }

    /**
     * @return $this
     */
    public function reset(): static
    {
        $this->handler = null;

        return $this;
    }

    protected function hasMocking(): bool
    {
        return !empty($this->mockingResponse);
    }

    /**
     * @return $this
     */
    public function appendResponse(
        int $statusCode,
        string $response,
        array $headers = [],
        string $version = '1.1',
        ?string $reason = null
    ): static {
        $this->mockingResponse[] = new Response(
            $statusCode,
            $headers,
            $response,
            $version,
            $reason
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function appendException(
        string $message,
        string $method,
        string $uri,
    ): static {
        $this->mockingResponse[] = new RequestException(
            $message,
            new Request($method, $uri)
        );

        return $this;
    }

    protected function initHistory(?int $id = null): static
    {
        if (isset($this->client)) {
            $id = spl_object_id($this->client);
        }

        if ($id && !isset($this->histories[$id])) {
            $this->histories[$id] = [];
        } else {
            $this->histories[0] = [];
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function enableHistory(int $id = 0): static
    {
        $this->initHistory($id)
            ->pushMiddleware(
                Middleware::history($this->histories[$id]),
                $this->hasMocking() ? 'fake' : null
            );

        return $this;
    }

    public function getHistories(): array
    {
        return $this->histories;
    }
}
