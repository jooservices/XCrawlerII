<?php

namespace Modules\Core\Tests\Unit\Observability;

use Modules\Core\Observability\RedactionService;
use Tests\TestCase;

class RedactionServiceTest extends TestCase
{
    private const REDACTED = '[REDACTED]';

    public function test_it_redacts_keys_case_insensitively_and_recursively(): void
    {
        config([
            'services.obs.redact_keys' => ['password', 'token', 'authorization'],
        ]);

        $service = app(RedactionService::class);

        $result = $service->redact([
            'Password' => 'root',
            'nested' => [
                'TOKEN' => 'abc',
                'Authorization' => 'Bearer demo',
                'safe' => 'value',
            ],
        ]);

        $this->assertSame(self::REDACTED, $result['Password']);
        $this->assertSame(self::REDACTED, $result['nested']['TOKEN']);
        $this->assertSame(self::REDACTED, $result['nested']['Authorization']);
        $this->assertSame('value', $result['nested']['safe']);
    }
}
