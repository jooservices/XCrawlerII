<?php

namespace Modules\Udemy\Services;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Bus;
use Modules\Udemy\Client\Dto\CourseCurriculumItemDto;
use Modules\Udemy\Client\Dto\CourseCurriculumItemsDto;
use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Events\CourseCurriculumItemAttachedEvent;
use Modules\Udemy\Events\SyncCourseCurriculumItemsCompletedEvent;
use Modules\Udemy\Jobs\SyncCourseCurriculumItemJob;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Repositories\UdemyCourseRepository;

class CourseService
{
    /**
     * @throws BindingResolutionException
     */
    public function syncCurriculumItem(
        UserToken $userToken,
        UdemyCourse $udemyCourse,
        array $payload = []
    ): ?CourseCurriculumItemsDto {
        $items = app(UdemySdk::class)
            ->setToken($userToken)
            ->courses()
            ->subscriberCurriculumItems($udemyCourse->id, $payload);

        if ($items === null) {
            return null;
        }

        $repository = app(UdemyCourseRepository::class);
        $items->getResults()->each(
            function (CourseCurriculumItemDto $item) use ($udemyCourse, $items, $userToken, $repository) {
                $curriculumItem = $repository->syncCurriculumItem($udemyCourse, $item);

                CourseCurriculumItemAttachedEvent::dispatch(
                    $userToken,
                    $udemyCourse,
                    $curriculumItem,
                    $items->getCount()
                );
            }
        );

        return $items;
    }

    /**
     * @throws BindingResolutionException
     */
    public function syncCurriculumItems(
        UserToken $userToken,
        UdemyCourse $udemyCourse,
        array $payload = []
    ): bool|CourseCurriculumItemsDto {
        $items = $this->syncCurriculumItem($userToken, $udemyCourse, $payload);
        $page = isset($payload['page']) ? (int) $payload['page'] : 1;

        if ($items === null) {
            return false;
        }

        if (
            $udemyCourse->items()->count() === $items->getCount()
        ) {
            SyncCourseCurriculumItemsCompletedEvent::dispatch(
                $userToken,
                $udemyCourse,
            );

            return true;
        }

        if (
            $items->pages() > 1
            && $items->pages() > $page
        ) {
            for ($index = 2; $index <= $items->pages(); $index++) {
                $batch[] = new SyncCourseCurriculumItemJob($userToken, $udemyCourse, $index);
            }
        }

        /**
         * @TODO Determine when chain completed
         */
        Bus::chain($batch)
            ->onQueue(UdemyService::UDEMY_QUEUE_NAME)
            ->dispatch();

        return $items;
    }
}
