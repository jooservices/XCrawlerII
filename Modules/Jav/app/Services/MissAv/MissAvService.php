<?php

namespace Modules\Jav\Services\MissAv;

use JsonException;
use Modules\Core\Facades\Setting;
use Modules\Core\Services\CoreService;
use Modules\Jav\Client\MissAv\CrawlingService;
use Modules\Jav\Exceptions\MissAvRecentUpdateFailed;
use Modules\Jav\Jobs\MissAv\FetchItemDetailJob;
use Modules\Jav\Jobs\MissAv\FetchItemsRecentUpdateJob;
use Modules\Jav\Models\MissAvReference;

final class MissAvService extends CoreService
{
    public function __construct(
        private readonly CrawlingService $crawlingService,
    ) {
    }

    /**
     * @throws JsonException
     */
    public function recentUpdate(?int $page = null): void
    {
        if (!$page) {
            $page = Setting::getInt('missav', 'recent_update_current_page', 1);
        }

        $items = $this->crawlingService->getItems(
            config(
                'jav.missav.recent_update',
                'https://missav123.com/dm514/en/new'
            ),
            $page,
        );

        if (!$items) {
            throw new MissAvRecentUpdateFailed();
        }

        $items->getItems()->each(function ($item) {
            $model = MissAvReference::updateOrCreate([
                'url' => $item->url,
            ], $item->toArray());

            FetchItemDetailJob::dispatch($model);
        });

        Setting::set('missav', 'recent_update_current_page', $page + 1);

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
