<?php

namespace Modules\Udemy\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Modules\Udemy\Events\BeforeProcessCompleteCurriculumItemEvent;
use Modules\Udemy\Events\Courses\CourseCreatedEvent;
use Modules\Udemy\Events\Courses\SyncMyCourseCompletedEvent;
use Modules\Udemy\Events\UserCourseStudyCompleted;
use Modules\Udemy\Events\UserHaveNoCoursesSubscribedEvent;
use Modules\Udemy\Jobs\StudyCurriculumItem;
use Modules\Udemy\Jobs\SyncCurriculumItemsJob;
use Modules\Udemy\Jobs\SyncMyCoursesJob;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Notifications\CourseReadyForStudyNotification;
use Modules\Udemy\Repositories\UdemyCourseRepository;
use Modules\Udemy\Repositories\UserTokenRepository;
use Modules\Udemy\Services\Client\Entities\CourseEntity;
use Modules\Udemy\Services\Client\Entities\CoursesEntity;
use Modules\Udemy\Services\Client\UdemySdk;
use Modules\Udemy\Services\CurriculumItems\Lecture;
use Modules\Udemy\Services\CurriculumItems\SimpleQuiz;
use Throwable;

class UdemyService
{
    public const string UDEMY_QUEUE_NAME = 'udemy';

    /**
     * @TODO
     * - type = 'coding-exercise'
     * @var array|string[]
     */
    private array $mappingCurriculumItems = [
        'lecture' => Lecture::class,
        'simple-quiz' => SimpleQuiz::class,
        'practice-test' => SimpleQuiz::class
    ];

    public function syncMyCourse(UserToken $userToken, array $payload = []): ?CoursesEntity
    {
        $coursesEntity = app(UdemySdk::class)
            ->me()
            ->subscribedCourses($userToken->token, $payload);

        if (!$coursesEntity) {
            UserHaveNoCoursesSubscribedEvent::dispatch($userToken);

            return null;
        }

        $repository = app(UdemyCourseRepository::class);
        $userTokenRepository = app(UserTokenRepository::class);

        $coursesEntity->getResults()->each(
            function (CourseEntity $courseEntity) use ($userToken, $repository, $userTokenRepository) {
                $udemyCourse = $repository->createFromEntity($courseEntity);
                $userTokenRepository->syncCourse(
                    $userToken,
                    $udemyCourse,
                    $courseEntity->completion_ratio ?? 0,
                    $courseEntity->enrollment_time
                        ? Carbon::parse($courseEntity->enrollment_time)
                        : null
                );

                if ($udemyCourse->wasRecentlyCreated && $udemyCourse->wasChanged() === false) {
                    CourseCreatedEvent::dispatch($userToken, $udemyCourse);
                }

                /**
                 * Always update items of course no matter reason
                 */
                SyncMyCourseCompletedEvent::dispatch($userToken, $udemyCourse);
            }
        );

        return $coursesEntity;
    }

    public function syncMyCourses(UserToken $userToken): void
    {
        SyncMyCoursesJob::dispatch($userToken);
    }

    public function syncCurriculumItems(UserToken $userToken, UdemyCourse $course): void
    {
        SyncCurriculumItemsJob::dispatch($userToken, $course);
    }

    public function completeCurriculumItems(UserToken $userToken, UdemyCourse $udemyCourse): void
    {
        if (config('udemy.notifications.enabled', false)) {
            $userToken->notify(new CourseReadyForStudyNotification($udemyCourse));
        }

        $completedIds = app(UdemySdk::class)->me()
            ->progress($userToken->token, $udemyCourse->id)
            ->getCompletedLectureIds();
        $items = $udemyCourse->items()
            ->whereNotIn('id', $completedIds)
            ->where('class', '<>', 'chapter')
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $itemsBatch = [];

        $items->each(function ($item) use ($userToken, &$itemsBatch) {
            /**
             * @TODO Exclude non action lecture
             * - chapter
             */
            $itemsBatch[] = new StudyCurriculumItem($userToken, $item);
        });

        /**
         * Batch of items for study
         * Each item will also break down to chains
         */
        Bus::batch($itemsBatch)->before(function (Batch $batch) {
            /**
             * @TODO Dispatch event and notification to let user know their course started
             */
        })->progress(function (Batch $batch) {
            // A single job has completed successfully...
        })->then(function (Batch $batch) use ($userToken, $udemyCourse) {
            UserCourseStudyCompleted::dispatch(
                $userToken,
                $udemyCourse
            );
        })->catch(function (Batch $batch, Throwable $e) {
            // First batch job failure detected...
        })->finally(function (Batch $batch) {
            /**
             * @TODO Dispatch event and notification to let user know their course completed
             */
        })->name(
            'Complete Curriculum Items: ' . $udemyCourse->title
        )->onQueue(UdemyService::UDEMY_QUEUE_NAME)->dispatch();
    }

    /**
     * @throws Exception
     */
    public function completeCurriculum(UserToken $userToken, CurriculumItem $curriculumItem): void
    {
        $class = $curriculumItem->class;

        if ($class === 'quiz') {
            $class = $curriculumItem->type;
        }

        if (!isset($this->mappingCurriculumItems[$class])) {
            return;
        }

        BeforeProcessCompleteCurriculumItemEvent::dispatch(
            $userToken,
            $curriculumItem,
            $this->mappingCurriculumItems[$class]
        );

        app($this->mappingCurriculumItems[$class])
            ->process($userToken, $curriculumItem);
    }

    public function attemptQuiz(
        UserToken      $userToken,
        CurriculumItem $curriculumItem
    ): bool
    {
        $sdk = app(UdemySdk::class);
        // Attempt created
        $attempt = $sdk->me()->userAttemptedQuizzes(
            $userToken->token,
            $curriculumItem
        );

        if ($attempt === false) {
            return false;
        }

        $assessments = ($sdk->quizzes()->assessments(
            $userToken->token,
            $curriculumItem
        ));

        /**
         * @TODO Break down to chains
         */
        foreach ($assessments->getResults() as $assessment) {
            $sdk->me()->assessmentAnswers(
                $userToken->token,
                $curriculumItem,
                $attempt,
                $assessment
            );
        }

        return true;
    }
}
