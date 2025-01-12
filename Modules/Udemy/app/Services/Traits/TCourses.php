<?php

namespace Modules\Udemy\Services\Traits;

use Exception;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Bus;
use Modules\Udemy\Client\Dto\CourseDto;
use Modules\Udemy\Client\Dto\CoursesDto;
use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Events\CourseCreatedEvent;
use Modules\Udemy\Events\SyncMyCourseCompletedEvent;
use Modules\Udemy\Events\SyncMyCourseFailedEvent;
use Modules\Udemy\Events\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Events\SyncMyCoursesProgressingEvent;
use Modules\Udemy\Jobs\SyncMyCourseJob;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Repositories\CourseRepository;
use Modules\Udemy\Repositories\UserTokenRepository;
use Throwable;

trait TCourses
{
    /**
     * @throws BindingResolutionException
     * @throws Exception
     */
    final public function syncMyCourse(
        UserToken $userToken,
        array $payload = [],
        bool $syncCurriculumItems = true
    ): ?CoursesDto {
        $coursesDto = app(UdemySdk::class)
            ->setToken($userToken)
            ->me()
            ->subscribedCourses($payload);

        if ($coursesDto === null) {
            SyncMyCourseFailedEvent::dispatch($userToken);

            return null;
        }

        $courseRepository = app(CourseRepository::class);
        $userTokenRepository = app(UserTokenRepository::class);

        $coursesDto->getResults()->each(
            function (CourseDto $courseDto) use (
                $userToken,
                $courseRepository,
                $userTokenRepository,
                $syncCurriculumItems
            ) {
                $udemyCourse = $courseRepository->createFromEntity($courseDto);
                $userTokenRepository->syncCourse(
                    $userToken,
                    $udemyCourse,
                    $courseDto->getCompletionRatio() ?? 0,
                    $courseDto->getEnrollmentTime()
                );

                if ($udemyCourse->wasRecentlyCreated && $udemyCourse->wasChanged() === false) {
                    CourseCreatedEvent::dispatch($userToken, $udemyCourse);
                }

                SyncMyCourseCompletedEvent::dispatch($userToken, $udemyCourse, $syncCurriculumItems);
            }
        );

        return $coursesDto;
    }

    /**
     * @throws BindingResolutionException|Throwable
     */
    final public function syncMyCourses(
        UserToken $userToken,
        array $payload = [],
        bool $syncCurriculumItems = true
    ): ?CoursesDto {
        /**
         * Sync first time to get init data
         *
         * @TODO Move to job
         */
        $coursesDto = $this->syncMyCourse($userToken, $payload, $syncCurriculumItems);

        if ($coursesDto === null) {
            return null;
        }

        $page = isset($payload['page']) ? (int) $payload['page'] : 1;

        if ($coursesDto->pages() <= $page) {
            return $coursesDto;
        }

        $batch = [];

        for ($index = 2; $index <= $coursesDto->pages(); $index++) {
            $batch[] = new SyncMyCourseJob($userToken, $index, $syncCurriculumItems);
        }

        Bus::batch($batch)->before(function (Batch $batch) {
            // The batch has been created but no jobs have been added...
        })->progress(function (Batch $batch) {
            SyncMyCoursesProgressingEvent::dispatch($batch);
        })->then(function (Batch $batch) {
            // All jobs completed successfully...
        })->catch(function (Batch $batch, Throwable $exception) {
            // First batch job failure detected...
        })->finally(function (Batch $batch) use ($userToken) {
            // The batch has finished executing...
            SyncMyCoursesCompletedEvent::dispatch($userToken);
        })
            ->name('Sync my courses ' . $userToken->id)
            ->onQueue(self::UDEMY_QUEUE_NAME)
            ->dispatch();

        return $coursesDto;
    }
}
