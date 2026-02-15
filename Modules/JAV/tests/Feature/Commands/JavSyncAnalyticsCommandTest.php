<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Modules\JAV\Tests\TestCase;

class JavSyncAnalyticsCommandTest extends TestCase
{
    public function test_command_rejects_invalid_days_input(): void
    {
        $this->artisan('jav:sync:analytics', [
            '--days' => [2, 99],
        ])->assertExitCode(2);
    }

    public function test_command_rejects_empty_valid_days_after_mixed_filtering(): void
    {
        $this->artisan('jav:sync:analytics', [
            '--days' => ['foo', -1, 0, 365],
        ])->assertExitCode(2);
    }

    public function test_command_rejects_non_numeric_and_out_of_range_day_tokens(): void
    {
        $this->artisan('jav:sync:analytics', [
            '--days' => ['abc', '5', '100', '-7'],
        ])->assertExitCode(2);
    }
}
