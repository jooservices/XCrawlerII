<?php

namespace Modules\JAV\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Jobs\SyncRecommendationSnapshotsJob;
use Modules\JAV\Services\JavManager;
use Modules\JAV\Services\UserLikeNotificationService;

class JavSubscriber implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        protected JavManager $javManager,
        protected UserLikeNotificationService $userLikeNotificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(ItemParsed $event): void
    {
        try {
            $item = $event->getItem();
            $source = $event->getSource();

            Log::info('JavSubscriber: Processing ItemParsed event', [
                'code' => $item->code,
                'source' => $source,
            ]);

            $jav = $this->javManager->store($item, $source);

            if (Cache::add("jav:recommendations:refresh:{$jav->id}", 1, now()->addMinutes(15))) {
                SyncRecommendationSnapshotsJob::dispatch((int) $jav->id, 30)
                    ->delay(now()->addSeconds(30))
                    ->onQueue('jav');
            }

            $notifiedUsers = 0;
            if ($jav->wasRecentlyCreated) {
                $notifiedUsers = $this->userLikeNotificationService->notifyForJav($jav);
            }

            Log::info('JavSubscriber: Successfully processed ItemParsed event', [
                'code' => $item->code,
                'source' => $source,
                'created' => $jav->wasRecentlyCreated,
                'notified_users' => $notifiedUsers,
            ]);
        } catch (\Exception $e) {
            Log::error('JavSubscriber: Failed to process ItemParsed event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Rethrow to trigger queue retry
            throw $e;
        }
    }
}
