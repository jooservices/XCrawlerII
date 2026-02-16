<?php

namespace Modules\JAV\Tests\Unit\Config;

use Modules\JAV\Tests\TestCase;

class HorizonQueueConfigTest extends TestCase
{
    public function test_horizon_wait_thresholds_include_all_jav_queues(): void
    {
        $waits = (array) config('horizon.waits', []);

        $this->assertArrayHasKey('redis:jav', $waits);
        $this->assertArrayHasKey('redis:jav-idol', $waits);
        $this->assertArrayHasKey('redis:onejav', $waits);
        $this->assertArrayHasKey('redis:141', $waits);
        $this->assertArrayHasKey('redis:xcity', $waits);
    }

    public function test_horizon_supervisors_match_required_worker_counts_and_timeouts(): void
    {
        $defaults = (array) config('horizon.defaults', []);

        $jav = (array) ($defaults['supervisor-jav'] ?? []);
        $onejav = (array) ($defaults['supervisor-onejav'] ?? []);
        $onefourone = (array) ($defaults['supervisor-141'] ?? []);
        $xcity = (array) ($defaults['supervisor-xcity'] ?? []);

        $this->assertSame(['jav', 'jav-idol'], $jav['queue'] ?? null);
        $this->assertSame(5, $jav['maxProcesses'] ?? null);
        $this->assertSame(5, $jav['minProcesses'] ?? null);
        $this->assertSame(3600, $jav['timeout'] ?? null);

        $this->assertSame(['onejav'], $onejav['queue'] ?? null);
        $this->assertSame(4, $onejav['maxProcesses'] ?? null);
        $this->assertSame(4, $onejav['minProcesses'] ?? null);
        $this->assertSame(3600, $onejav['timeout'] ?? null);

        $this->assertSame(['141'], $onefourone['queue'] ?? null);
        $this->assertSame(4, $onefourone['maxProcesses'] ?? null);
        $this->assertSame(4, $onefourone['minProcesses'] ?? null);
        $this->assertSame(3600, $onefourone['timeout'] ?? null);

        $this->assertSame(['xcity', 'jav-idol'], $xcity['queue'] ?? null);
            $this->assertSame(['xcity', 'jav-idol'], $xcity['queue'] ?? null);
        $this->assertSame(2, $xcity['maxProcesses'] ?? null);
        $this->assertSame(2, $xcity['minProcesses'] ?? null);
        $this->assertSame(3600, $xcity['timeout'] ?? null);
    }

    public function test_horizon_retry_policy_matches_required_backoff_schedule(): void
    {
        $defaults = (array) config('horizon.defaults', []);

        foreach (['supervisor-jav', 'supervisor-onejav', 'supervisor-141', 'supervisor-xcity'] as $key) {
            $supervisor = (array) ($defaults[$key] ?? []);

            $this->assertSame(4, $supervisor['tries'] ?? null, "{$key} tries mismatch");
            $this->assertSame([1800, 2700, 3600], $supervisor['backoff'] ?? null, "{$key} backoff mismatch");
        }
    }
}
