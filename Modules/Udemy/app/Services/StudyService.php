<?php

namespace Modules\Udemy\Services;

use Illuminate\Bus\Batch;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Bus;
use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Events\StudyInProgressEvent;
use Modules\Udemy\Jobs\StudyCurriculumItemJob;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Throwable;

class StudyService
{
    /**
     * @throws BindingResolutionException
     * @throws Throwable
     */
    final public function study(
        UserToken $userToken,
        UdemyCourse $udemyCourse
    ): void {
        /**
         * @TODO Notification
         */
        $ids = app(UdemySdk::class)
            ->setToken($userToken)
            ->getCompletedIds($udemyCourse->id);

        /**
         * Split $itemsBatch to multi batches
         */
        $itemsBatch = [];
        $items = $udemyCourse->items()
            ->whereNotIn('id', $ids)
            ->where('class', '<>', 'chapter')
            ->get();

        $items->each(function ($item) use ($userToken, &$itemsBatch) {
            $itemsBatch[] = new StudyCurriculumItemJob($userToken, $item);
        });

        if (empty($itemsBatch)) {
            return;
        }

        Bus::batch($itemsBatch)
            ->progress(function (Batch $batch) use ($userToken, $udemyCourse) {
                StudyInProgressEvent::dispatch($userToken, $udemyCourse, $batch);
            })->name(
                'Complete curriculum items: ' . $udemyCourse->title
            )->onQueue(UdemyService::UDEMY_QUEUE_NAME)->dispatch();
    }

    /**
     * This one will be called inside a job
     */
    final public function studyCurriculum(
        UserToken $userToken,
        CurriculumItem $curriculumItem
    ): void {
        app(StudyManager::class)->study($userToken, $curriculumItem);
    }
}
