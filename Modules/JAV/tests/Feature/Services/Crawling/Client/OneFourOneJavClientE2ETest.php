<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Feature\Services\Crawling\Client;

use JOOservices\Client\Contracts\ResponseWrapperInterface;
use Modules\Core\Models\MongoDb\ClientLog;
use Modules\JAV\Services\Crawling\Client\OneFourOneJavClient;
use Modules\JAV\Tests\TestCase;

/**
 * @group integration
 */
final class OneFourOneJavClientE2ETest extends TestCase
{
    public function test_get_home_returns_success_and_logs_with_user_agent(): void
    {
        $client = app(OneFourOneJavClient::class);
        $tag = 'e2e-jav-141jav-' . uniqid('', true);
        $response = $client->get('/', ['tags' => [$tag]]);

        $this->assertInstanceOf(ResponseWrapperInterface::class, $response);
        $psr = $response->toPsrResponse();
        $this->assertSame(200, $psr->getStatusCode());
        $this->assertNotEmpty((string) $psr->getBody());

        $log = ClientLog::query()
            ->where('site', 'www.141jav.com')
            ->where('path', '/')
            ->where('status', 200)
            ->where('tags', $tag)
            ->latest('ts')
            ->first();

        if ($log === null) {
            $log = ClientLog::query()
                ->where('site', 'www.141jav.com')
                ->where('path', '/')
                ->where('status', 200)
                ->latest('ts')
                ->first();
        }

        $this->assertNotNull($log, 'Request should be logged');
        $headers = $log->request['headers'] ?? [];
        $userAgent = $headers['user-agent'] ?? $headers['User-Agent'] ?? null;
        $this->assertNotEmpty($userAgent, 'User-Agent should be sent and logged');
    }
}
