<?php

namespace Modules\Core\Tests\Feature\Commands;

use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityTotals;
use Modules\Core\Tests\TestCase;
use Modules\JAV\Models\Jav;

class AnalyticsParityCheckCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->requireMongo();
        AnalyticsEntityTotals::query()->delete();
    }

    public function test_command_returns_success_when_no_mismatch(): void
    {
        $jav = Jav::factory()->create([
            'views' => 10,
            'downloads' => 3,
            'source' => 'onejav',
            'code' => 'ABC-001',
        ]);

        AnalyticsEntityTotals::query()->updateOrCreate(
            [
                'domain' => 'jav',
                'entity_type' => 'movie',
                'entity_id' => $jav->uuid,
            ],
            [
                'view' => 10,
                'download' => 3,
            ]
        );

        $this->artisan('analytics:parity-check')
            ->expectsOutput('Checked: 1, Mismatches: 0')
            ->assertExitCode(0);
    }

    public function test_command_returns_failure_and_prints_mismatch_rows(): void
    {
        $jav = Jav::factory()->create([
            'views' => 10,
            'downloads' => 3,
            'source' => 'onejav',
            'code' => 'ABC-002',
        ]);

        AnalyticsEntityTotals::query()->updateOrCreate(
            [
                'domain' => 'jav',
                'entity_type' => 'movie',
                'entity_id' => $jav->uuid,
            ],
            [
                'view' => 9,
                'download' => 3,
            ]
        );

        $this->artisan('analytics:parity-check', ['--limit' => 5])
            ->expectsOutput('ABC-002: MySQL(10/3) vs Mongo(9/3)')
            ->expectsOutput('Checked: 1, Mismatches: 1')
            ->assertExitCode(1);
    }

    private function requireMongo(): void
    {
        try {
            AnalyticsEntityTotals::query()->limit(1)->get();
        } catch (\Throwable $exception) {
            $this->markTestSkipped('MongoDB unavailable for command integration test: '.$exception->getMessage());
        }
    }
}
