<?php

declare(strict_types=1);

namespace Modules\Core\Services\Client\Contracts;

use Psr\Http\Message\ResponseInterface;

interface ClientContract
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface;
}
