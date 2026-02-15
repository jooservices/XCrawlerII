<?php

namespace Modules\JAV\Tests\Unit\Services;

use App\Models\User;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Favorite;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Rating;
use Modules\JAV\Models\Tag;
use Modules\JAV\Models\UserJavHistory;
use Modules\JAV\Models\Watchlist;
use Modules\JAV\Services\AnalyticsSnapshotService;
use Modules\JAV\Tests\TestCase;

class AnalyticsSnapshotServiceTest extends TestCase
{
    public function test_get_snapshot_returns_expected_metrics_payload_shape_and_counts(): void
    {
        $user = User::factory()->create();

        $linkedActor = Actor::factory()->create();
        $orphanActor = Actor::factory()->create();
        $linkedTag = Tag::factory()->create();
        $orphanTag = Tag::factory()->create();

        $completeJav = Jav::factory()->create([
            'source' => 'onejav',
            'image' => 'https://example.com/cover.jpg',
            'date' => now(),
            'views' => 100,
            'downloads' => 50,
            'created_at' => now(),
        ]);
        $completeJav->actors()->attach($linkedActor->id);
        $completeJav->tags()->attach($linkedTag->id);

        $incompleteJav = Jav::factory()->create([
            'source' => '',
            'image' => '',
            'date' => null,
            'views' => 1,
            'downloads' => 1,
            'created_at' => now(),
        ]);

        Favorite::factory()->create([
            'user_id' => $user->id,
            'favoritable_id' => $completeJav->id,
            'favoritable_type' => Jav::class,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Watchlist::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $completeJav->id,
            'status' => 'to_watch',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Rating::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $completeJav->id,
            'rating' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        UserJavHistory::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $completeJav->id,
            'action' => 'view',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(AnalyticsSnapshotService::class);
        $payload = $service->getSnapshot(7, true);

        $this->assertSame(7, $payload['days']);
        $this->assertSame(2, $payload['totals']['jav']);
        $this->assertSame(2, $payload['totals']['actors']);
        $this->assertSame(2, $payload['totals']['tags']);

        $this->assertSame(1, $payload['quality']['missing_actors']);
        $this->assertSame(1, $payload['quality']['missing_tags']);
        $this->assertSame(1, $payload['quality']['missing_image']);
        $this->assertSame(1, $payload['quality']['missing_date']);
        $this->assertSame(1, $payload['quality']['orphan_actors']);
        $this->assertSame(1, $payload['quality']['orphan_tags']);

        $this->assertCount(7, $payload['dailyCreated']['jav']['labels']);
        $this->assertCount(7, $payload['dailyCreated']['jav']['values']);
        $this->assertCount(7, $payload['dailyEngagement']['favorites']['labels']);
        $this->assertCount(7, $payload['dailyEngagement']['favorites']['values']);

        $this->assertSame($completeJav->uuid, $payload['topViewed'][0]['uuid']);
        $this->assertSame($completeJav->uuid, $payload['topDownloaded'][0]['uuid']);

        $sources = collect($payload['providerStats'])->pluck('source')->all();
        $this->assertContains('onejav', $sources);
        $this->assertContains('unknown', $sources);
    }

    public function test_sync_snapshots_returns_payloads_for_each_requested_window(): void
    {
        Jav::factory()->count(2)->create();

        $service = app(AnalyticsSnapshotService::class);
        $synced = $service->syncSnapshots([7, 14]);

        $this->assertArrayHasKey(7, $synced);
        $this->assertArrayHasKey(14, $synced);
        $this->assertSame(7, $synced[7]['days']);
        $this->assertSame(14, $synced[14]['days']);
    }
}
