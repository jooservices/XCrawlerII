<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Models;

use Modules\Core\Database\Factories\ClientLogFactory;
use Modules\Core\Models\ClientLog;
use Modules\Core\Tests\TestCase;
use MongoDB\BSON\UTCDateTime;

final class ClientLogTest extends TestCase
{
    public function test_from_http_lifecycle_normalizes_required_fields(): void
    {
        $faker = fake();
        $payload = ClientLogFactory::new()->make()->toArray();
        $payload['status'] = 502;
        $payload['attempt'] = 3;
        $payload['max_attempts'] = 3;
        $payload['site'] = $faker->domainName();
        $payload['ts'] = new \DateTimeImmutable();

        $doc = ClientLog::fromHttpLifecycle($payload);

        $this->assertInstanceOf(UTCDateTime::class, $doc['ts']);
        $this->assertSame(2, $doc['retries']);
        $this->assertFalse($doc['ok']);
        $this->assertSame($payload['site'], $doc['site']);
    }

    public function test_from_http_lifecycle_handles_weird_and_missing_values(): void
    {
        $doc = ClientLog::fromHttpLifecycle([
            'attempt' => 0,
            'max_attempts' => 0,
            'status' => null,
            'request' => 'invalid',
            'response' => false,
            'cache' => null,
            'tags' => 'invalid',
        ]);

        $this->assertSame(1, $doc['attempt']);
        $this->assertSame(0, $doc['retries']);
        $this->assertSame(1, $doc['max_attempts']);
        $this->assertSame([], $doc['request']);
        $this->assertSame([], $doc['response']);
        $this->assertSame([], $doc['cache']);
        $this->assertSame([], $doc['tags']);
        $this->assertFalse($doc['ok']);
    }
}
