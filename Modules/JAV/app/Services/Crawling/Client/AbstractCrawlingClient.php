<?php

declare(strict_types=1);

namespace Modules\JAV\Services\Crawling\Client;

use JOOservices\Client\Contracts\ResponseWrapperInterface;
use Modules\Core\Services\Client\Client;

abstract class AbstractCrawlingClient
{
    public function __construct(
        protected readonly Client $client,
    ) {
    }

    public function get(string $path, array $options = []): ResponseWrapperInterface
    {
        return $this->request('GET', $path, $options);
    }

    public function request(string $method, string $path, array $options = []): ResponseWrapperInterface
    {
        $url = $this->getUrl($path);
        $options = $this->mergeUserAgent($options);

        return $this->client->request($method, $url, $options);
    }

    abstract protected function getUrl(string $path): string;

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function mergeUserAgent(array $options): array
    {
        $headers = $options['headers'] ?? [];
        $headersLower = array_change_key_case($headers, CASE_LOWER);
        $hasUA = isset($headersLower['user-agent']);
        if (! $hasUA) {
            $options['headers'] = array_merge(
                ['User-Agent' => \JOOservices\UserAgent\UserAgent::generate()],
                $headers
            );
        }

        return $options;
    }
}
