<?php

namespace Modules\Jav\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use Modules\Core\Services\SettingService;
use Modules\Jav\Events\OnejavMovieCreatedEvent;
use Modules\Jav\Events\OnejavReferenceCreatedEvent;
use Modules\Jav\Onejav\CrawlingService;
use Modules\Jav\Repositories\OnejavRepository;
use Modules\Jav\Services\OnejavService;
use Throwable;

class OnejavFetchItemsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $endpoint,
        public int $page = 1
    ) {
        $this->onQueue(OnejavService::ONEJAV_QUEUE_NAME);
    }

    /**
     * Execute the job.
     */
    public function handle(CrawlingService $service): void
    {
        $items = $service->getItems($this->endpoint, $this->page);
        app(SettingService::class)
            ->set(
                'onejav',
                $this->endpoint . '_current_page',
                $this->page + 1
            );

        $repository = app(OnejavRepository::class);

        foreach ($items as $item) {
            $model = $repository->insert($item->toArray());

            if ($model->wasRecentlyCreated) {
                OnejavMovieCreatedEvent::dispatch($model);
            }

            Event::dispatch(new OnejavReferenceCreatedEvent($model));
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        /**
         * @TODO Should we dispatch another
         */
    }
}
