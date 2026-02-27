<?php

declare(strict_types=1);

namespace Modules\Core\Services\Client\Middleware;

use Closure;
use JOOservices\Client\Contracts\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class RetryTrackingMiddleware implements MiddlewareInterface
{
    public const CONTEXT_KEY = '_xcrawler_context';

    public function __invoke(RequestInterface $request, array $options, Closure $next): ResponseInterface
    {
        $context = $options[self::CONTEXT_KEY] ?? null;

        if ($context instanceof \ArrayObject) {
            $attempt = (int) ($context['attempt'] ?? 0);
            $context['attempt'] = $attempt + 1;
            $context['retries'] = max(0, $context['attempt'] - 1);
        }

        return $next($request, $options);
    }
}
