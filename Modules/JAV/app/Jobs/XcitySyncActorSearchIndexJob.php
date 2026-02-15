<?php

namespace Modules\JAV\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Modules\JAV\Models\Actor;

class XcitySyncActorSearchIndexJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public string $xcityId
    ) {}

    public function handle(): void
    {
        $flagKey = $this->indexFlagKey();
        $shouldIndex = Cache::get($flagKey, false);
        Cache::forget($flagKey);

        if ($shouldIndex !== true) {
            return;
        }

        $actor = Actor::query()->where('xcity_id', $this->xcityId)->first();
        if ($actor === null) {
            return;
        }

        $actor->searchable();
    }

    private function indexFlagKey(): string
    {
        return 'xcity:index_actor:' . $this->xcityId;
    }
}
