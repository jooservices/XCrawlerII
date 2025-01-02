<?php

namespace Modules\Jav\Services\MissAv;

use JsonException;
use Modules\Jav\Client\MissAv\CrawlingService;
use Modules\Jav\Jobs\MissAv\FetchItemDetailJob;
use Modules\Jav\Jobs\MissAv\FetchItemsRecentUpdateJob;
use Modules\Jav\Models\MissAvReference;

final readonly class MissAvService
{
    public function __construct(
        private CrawlingService $crawlingService,
    ) {
    }

    /**
     * @throws JsonException
     */
    public function recentUpdate(int $page = 1): void
    {
        $items = $this->crawlingService->getItems(
            config('jav.missav.recent_update', 'https://missav123.com/dm514/en/new'),
            $page,
        );

        $items->getItems()->each(function ($item) {
            $model = MissAvReference::updateOrCreate([
                'url' => $item->url,
            ], $item->toArray());

            FetchItemDetailJob::dispatch($model);
        });

        for ($index = 2; $index <= $items->getLastPage(); $index++) {
            FetchItemsRecentUpdateJob::dispatch($index);
        }
    }

    final public function updateDetail(MissAvReference $model): bool
    {
        $item = $this->crawlingService->itemDetail($model->url);

        return $model->update($item->toArray());
    }
}
