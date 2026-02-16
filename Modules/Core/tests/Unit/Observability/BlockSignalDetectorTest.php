<?php

namespace Modules\Core\Tests\Unit\Observability;

use Modules\Core\Observability\BlockSignalDetector;
use Tests\TestCase;

class BlockSignalDetectorTest extends TestCase
{
    public function test_it_detects_rate_limit_signal_from_error_code(): void
    {
        $detector = app(BlockSignalDetector::class);

        $signal = $detector->detect([
            'error_code' => 429,
            'site' => 'xcity.jp',
            'queue' => 'jav-idol',
        ]);

        $this->assertNotNull($signal);
        $this->assertSame(429, $signal['http_status']);
        $this->assertSame('rate_limit', $signal['block_signal_type']);
        $this->assertSame(300, $signal['cooldown_seconds']);
        $this->assertSame('xcity.jp', $signal['target_host']);
    }

    public function test_it_detects_forbidden_signal_from_error_message(): void
    {
        $detector = app(BlockSignalDetector::class);

        $signal = $detector->detect([
            'error_message_short' => 'HTTP 403 Forbidden from upstream',
            'url' => 'https://example.org/resource',
        ]);

        $this->assertNotNull($signal);
        $this->assertSame(403, $signal['http_status']);
        $this->assertSame('forbidden', $signal['block_signal_type']);
        $this->assertSame('example.org', $signal['target_host']);
    }

    public function test_it_returns_null_for_non_blocking_errors(): void
    {
        $detector = app(BlockSignalDetector::class);

        $signal = $detector->detect([
            'error_code' => 500,
            'error_message_short' => 'General failure',
        ]);

        $this->assertNull($signal);
    }
}
