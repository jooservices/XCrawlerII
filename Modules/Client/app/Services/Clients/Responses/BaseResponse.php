<?php

namespace Modules\Client\Services\Clients\Responses;

use Modules\Client\Interfaces\IResponse;
use Modules\Client\Interfaces\IResponseData;
use Modules\Client\Services\Clients\ResponseData\BaseResponseData;
use Modules\Client\Services\Clients\ResponseData\DomResponseData;
use Modules\Client\Services\Clients\ResponseData\JsonResponseData;
use Modules\Client\Services\RequestLogService;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class BaseResponse implements IResponse
{
    private string $body;

    private array $mappingResponseData = [
        'application/json' => JsonResponseData::class,
        'application/json, text/plain' => JsonResponseData::class,
        'text/html; charset="utf-8"' => DomResponseData::class,
        'text/html; charset=utf-8' => DomResponseData::class,
        'text/html; charset=UTF-8' => DomResponseData::class,
        'text/html' => DomResponseData::class,
    ];

    public function __construct(private readonly ?ResponseInterface $response = null)
    {
        if ($this->response !== null) {
            $this->body = $this->response->getBody()->getContents();

            app(RequestLogService::class)->respond(
                $this->response->getStatusCode(),
                $this->body,
            );
        }
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function isSuccess(): bool
    {
        return
            $this->response !== null &&
            $this->getStatusCode() >= Response::HTTP_OK
            && $this->getStatusCode() < Response::HTTP_MULTIPLE_CHOICES;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function parseBody(): IResponseData
    {
        if (!isset($this->mappingResponseData[$this->getContentType()])) {
            return new BaseResponseData($this->body);
        }

        return new $this->mappingResponseData[$this->getContentType()]($this->body);
    }

    public function getContentType(): string
    {
        $headers = $this->response
            ? $this->response->getHeader('Content-Type')
            : ['text/html'];

        return reset($headers);
    }

    public function toJson(): string
    {
        return json_encode($this->body);
    }
}
