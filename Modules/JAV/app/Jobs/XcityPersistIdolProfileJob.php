<?php

namespace Modules\JAV\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Modules\JAV\Services\XcityIdolService;

class XcityPersistIdolProfileJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public int $timeout = 3600;

    public function __construct(
        public string $xcityId,
        public string $name,
        public string $detailUrl,
        public ?string $coverImage
    ) {}

    public function handle(XcityIdolService $service): void
    {
        $shouldIndex = $service->syncIdolFromListItem(
            xcityId: $this->xcityId,
            name: $this->name,
            detailUrl: $this->detailUrl,
            coverImage: $this->coverImage
        );

        Cache::put($this->indexFlagKey(), $shouldIndex, now()->addMinutes(30));
    }

    public function backoff(): array
    {
        return [1800, 2700, 3600];
    }

    private function indexFlagKey(): string
    {
        return 'xcity:index_actor:'.$this->xcityId;
    }
}
