<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\Client;

use JOOservices\Client\Contracts\ResponseWrapperInterface;

interface ClientContract
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function request(string $method, string $url, array $options = []): ResponseWrapperInterface;
}
