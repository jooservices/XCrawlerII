<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Modules\JAV\Events\ContentSynced;
use Modules\JAV\Events\ContentSyncing;
use Modules\JAV\Jobs\XcitySyncActorSearchIndexJob;
use Modules\JAV\Models\Actor;
use Modules\JAV\Tests\TestCase;

class XcitySyncActorSearchIndexJobTest extends TestCase
{
    public function test_job_uses_batchable_trait(): void
    {
        $traits = class_uses_recursive(XcitySyncActorSearchIndexJob::class);

        $this->assertContains(Batchable::class, $traits);
    }

    public function test_handle_noops_when_index_flag_is_false_and_clears_flag(): void
    {
        Cache::put('xcity:index_actor:2002', false, now()->addMinutes(30));

        $job = new XcitySyncActorSearchIndexJob('2002');
        $job->handle();

        $this->assertNull(Cache::get('xcity:index_actor:2002'));
    }

    public function test_handle_indexes_actor_when_flag_is_true_and_actor_exists(): void
    {
        config(['scout.driver' => 'collection']);
        Event::fake([ContentSyncing::class, ContentSynced::class]);

        $actor = Actor::factory()->create(['xcity_id' => '3003']);
        Cache::put('xcity:index_actor:3003', true, now()->addMinutes(30));

        $job = new XcitySyncActorSearchIndexJob('3003');
        $job->handle();

        $this->assertNull(Cache::get('xcity:index_actor:3003'));

        Event::assertDispatched(ContentSyncing::class, function (ContentSyncing $event) use ($actor): bool {
            return (int) $event->model->id === (int) $actor->id;
        });

        Event::assertDispatched(ContentSynced::class, function (ContentSynced $event) use ($actor): bool {
            return (int) $event->model->id === (int) $actor->id;
        });
    }
}
