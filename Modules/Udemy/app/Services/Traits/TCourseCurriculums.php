<?php

namespace Modules\Udemy\Services\Traits;

use Illuminate\Support\Facades\Bus;
use Modules\Udemy\Client\Dto\CourseCurriculumItemDto;
use Modules\Udemy\Client\Dto\CourseCurriculumItemsDto;
use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Jobs\SyncCourseCurriculumItemJob;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Repositories\CourseRepository;
use Modules\Udemy\Services\UdemyService;

trait TCourseCurriculums
{
    final public function syncCurriculumItem(
        UserToken $userToken,
        UdemyCourse $udemyCourse,
        array $payload = []
    ): ?CourseCurriculumItemsDto {
        $items = app(UdemySdk::class)
            ->setToken($userToken)
            ->courses()
            ->subscriberCurriculumItems($udemyCourse->id, $payload);

        if ($items === null) {
            /**
             * @TODO Failed event
             */
            return null;
        }

        $repository = app(CourseRepository::class);
        $items->getResults()->each(
            function (CourseCurriculumItemDto $item) use ($udemyCourse, $repository) {
                $repository->syncCurriculumItem($udemyCourse, $item);
            }
        );

        return $items;
    }

    final public function syncCurriculumItems(
        UserToken $userToken,
        UdemyCourse $udemyCourse,
        array $payload = []
    ): ?CourseCurriculumItemsDto {
        $items = $this->syncCurriculumItem($userToken, $udemyCourse, $payload);

        if ($items === null) {
            return null;
        }

        $page = isset($payload['page']) ? (int) $payload['page'] : 1;

        $batch = [];
        if ($items->pages() > $page) {
            for ($index = 2; $index <= $items->pages(); $index++) {
                $batch[] = new SyncCourseCurriculumItemJob($userToken, $udemyCourse, $index);
            }

            /**
             * @TODO Determine when chain completed
             */
            Bus::chain($batch)
                ->onQueue(UdemyService::UDEMY_QUEUE_NAME)
                ->dispatch();
        }

        return $items;
    }
}
