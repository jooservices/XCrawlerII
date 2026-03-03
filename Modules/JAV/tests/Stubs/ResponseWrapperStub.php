<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Stubs;

use JOOservices\Client\Contracts\ResponseWrapperInterface;
use Psr\Http\Message\ResponseInterface;

final class ResponseWrapperStub implements ResponseWrapperInterface
{
    public function __construct(
        private readonly ResponseInterface $response,
    ) {
    }

    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    public function header(string $name): ?string
    {
        $line = $this->response->getHeaderLine($name);

        return $line !== '' ? $line : null;
    }

    public function json(): array
    {
        $body = (string) $this->response->getBody();

        return json_decode($body, true, 512, JSON_THROW_ON_ERROR) ?? [];
    }

    public function toPsrResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function toDto(string $dtoClass): object
    {
        throw new \BadMethodCallException('toDto() is not implemented in test stub');
    }
}
