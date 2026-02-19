<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users;

use Modules\Core\Services\AnalyticsIngestService;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\UserJavHistory;
use Modules\JAV\Tests\TestCase;

class MovieDownloadGuestBehaviorTest extends TestCase
{
    public function test_guest_download_tracks_event_but_does_not_create_history_record(): void
    {
        config(['analytics.enabled' => true]);

        $jav = Jav::factory()->create([
            'source' => 'unsupported-source',
            'downloads' => 0,
        ]);

        $service = \Mockery::mock(AnalyticsIngestService::class);
        $service->shouldReceive('ingest')->once();
        $this->app->instance(AnalyticsIngestService::class, $service);

        $this->from(route('jav.vue.dashboard'))
            ->get(route('jav.movies.download', $jav))
            ->assertRedirect(route('jav.vue.dashboard'));

        $this->assertSame(0, (int) $jav->fresh()->downloads);

        $historyCount = UserJavHistory::query()
            ->where('jav_id', $jav->id)
            ->where('action', 'download')
            ->count();

        $this->assertSame(0, $historyCount);
    }
}
