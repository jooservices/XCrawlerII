<?php

namespace Modules\Udemy\Services;

use Exception;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Bus;
use Modules\Udemy\Client\Dto\CourseDto;
use Modules\Udemy\Client\Dto\CoursesDto;
use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Events\CourseCreatedEvent;
use Modules\Udemy\Events\SyncMyCourseCompletedEvent;
use Modules\Udemy\Events\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Events\SyncMyCoursesFinishedEvent;
use Modules\Udemy\Events\SyncMyCoursesProgressingEvent;
use Modules\Udemy\Events\UserAttachedCourseEvent;
use Modules\Udemy\Events\UserSyncMyCourseFailedEvent;
use Modules\Udemy\Jobs\SyncMyCourseJob;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Repositories\UdemyCourseRepository;
use Modules\Udemy\Repositories\UserTokenRepository;
use Throwable;

class UdemyService
{
    public const string UDEMY_QUEUE_NAME = 'udemy';

    /**
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function syncMyCourse(
        UserToken $userToken,
        array $payload = []
    ): ?CoursesDto {
        $coursesDto = app(UdemySdk::class)
            ->setToken($userToken)
            ->me()
            ->subscribedCourses($payload);

        if ($coursesDto === null) {
            UserSyncMyCourseFailedEvent::dispatch($userToken);

            return null;
        }

        $courseRepository = app(UdemyCourseRepository::class);
        $userTokenRepository = app(UserTokenRepository::class);

        $coursesDto->getResults()->each(
            function (CourseDto $courseDto) use (
                $userToken,
                $courseRepository,
                $userTokenRepository
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

                /**
                 * @TODO Only dispatch when it's first time attached
                 */
                UserAttachedCourseEvent::dispatch($userToken, $udemyCourse);

                SyncMyCourseCompletedEvent::dispatch($userToken, $udemyCourse);
            }
        );

        return $coursesDto;
    }

    /**
     * @throws BindingResolutionException|Throwable
     */
    public function syncMyCourses(
        UserToken $userToken,
        array $payload = [],
    ): bool|CoursesDto {
        /**
         * Sync first time to get init data
         */
        $coursesDto = $this->syncMyCourse($userToken, $payload);
        $page = isset($payload['page']) ? (int) $payload['page'] : 1;

        if ($coursesDto === null) {
            return false;
        }

        if (
            $coursesDto->pages() > 1
            && $coursesDto->pages() > $page
        ) {
            $batch = [];

            for ($index = 2; $index <= $coursesDto->pages(); $index++) {
                $batch[] = new SyncMyCourseJob($userToken, $index);
            }

            Bus::batch($batch)->before(function (Batch $batch) {
                // The batch has been created but no jobs have been added...
            })->progress(function (Batch $batch) {
                SyncMyCoursesProgressingEvent::dispatch($batch);
            })->then(function (Batch $batch) use ($userToken) {
                SyncMyCoursesCompletedEvent::dispatch(
                    $userToken,
                    $batch
                );
            })->catch(function (Batch $batch, Throwable $e) {
                // First batch job failure detected...
            })->finally(function (Batch $batch) use ($userToken, $coursesDto) {
                /**
                 * Everything completed finished
                 */
                SyncMyCoursesFinishedEvent::dispatch(
                    $batch,
                    $userToken,
                    $coursesDto
                );
            })
                ->name('Sync my courses ' . $userToken->id)
                ->onQueue(UdemyService::UDEMY_QUEUE_NAME)
                ->dispatch();
        }

        return $coursesDto;
    }
}
