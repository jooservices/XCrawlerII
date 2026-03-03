<?php

declare(strict_types=1);

namespace Modules\JAV\Services\Crawling\Client;

final class OneFourOneJavClient extends AbstractCrawlingClient
{
    public const string BASE_URI = 'https://www.141jav.com';

    protected function getUrl(string $path): string
    {
        $base = rtrim(self::BASE_URI, '/');
        $path = ltrim($path, '/');

        return $path !== '' ? $base . '/' . $path : $base;
    }
}
