<?php

namespace Modules\JAV\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\JAV\Events\ItemParsed;
use Modules\JAV\Services\JavManager;

class JavSubscriber implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        protected JavManager $javManager
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

            $this->javManager->store($item, $source);

            Log::info('JavSubscriber: Successfully processed ItemParsed event', [
                'code' => $item->code,
                'source' => $source,
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
