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
use Modules\Udemy\Notifications\CourseReadyForStudyNotif;
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
            ->me()->progress($udemyCourse->id)->getCompletedIds();

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

        //$userToken->notify(new CourseReadyForStudyNotif($udemyCourse));

        Bus::batch($itemsBatch)->before(function (Batch $batch) {
            /**
             * @TODO Dispatch event and notification to let user know their course started
             */
        })->progress(function (Batch $batch) use ($userToken, $udemyCourse) {
            StudyInProgressEvent::dispatch($userToken, $udemyCourse, $batch);
        })->then(function (Batch $batch) {
        })->catch(function (Batch $batch, Throwable $e) {
            // First batch job failure detected...
        })->finally(function (Batch $batch) {
            /**
             * @TODO Dispatch event and notification to let user know their course completed
             */
        })->name(
            'Complete curriculum items: ' . $udemyCourse->title
        )->onQueue(UdemyService::UDEMY_QUEUE_NAME)->dispatch();
    }

    /**
     * This one will be called inside a job
     */
    public function studyCurriculum(
        UserToken $userToken,
        CurriculumItem $curriculumItem
    ): void {
        app(StudyManager::class)->study($userToken, $curriculumItem);
    }
}
