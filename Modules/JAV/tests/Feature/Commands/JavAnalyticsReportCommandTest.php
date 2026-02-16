<?php

namespace Modules\JAV\Tests\Feature\Commands;

use Illuminate\Support\Facades\Artisan;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Tests\TestCase;

class JavAnalyticsReportCommandTest extends TestCase
{
    public function test_command_can_render_json_report_with_provider_and_xcity_sections(): void
    {
        Jav::factory()->create([
            'source' => 'onejav',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Jav::factory()->create([
            'source' => '141jav',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        Jav::factory()->create([
            'source' => 'ffjav',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Actor::factory()->create([
            'xcity_id' => '12345',
            'xcity_url' => 'https://xxx.xcity.jp/idol/detail/12345/',
            'xcity_cover' => 'https://example.com/cover.jpg',
            'xcity_synced_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $exitCode = Artisan::call('jav:analytics:report', [
            '--days' => 14,
            '--json' => true,
            '--limit' => 5,
        ]);

        $this->assertSame(0, $exitCode);

        $payload = json_decode(Artisan::output(), true);

        $this->assertIsArray($payload);
        $this->assertSame(14, $payload['days']);
        $this->assertArrayHasKey('provider_breakdown', $payload);
        $this->assertArrayHasKey('xcity', $payload);
        $this->assertArrayHasKey('quality', $payload);
        $this->assertArrayHasKey('telemetry', $payload);

        $sources = collect($payload['provider_breakdown'])->pluck('source')->all();
        $this->assertContains('onejav', $sources);
        $this->assertContains('141jav', $sources);
        $this->assertContains('ffjav', $sources);
    }

    public function test_command_rejects_invalid_days_range(): void
    {
        $this->artisan('jav:analytics:report', [
            '--days' => 0,
        ])->assertExitCode(2);
    }
}
