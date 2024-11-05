<?php

namespace Modules\Udemy\Services;

use Exception;
use Modules\Udemy\Events\BeforeProcessCompleteCurriculumItemEvent;
use Modules\Udemy\Jobs\SyncCurriculumItemsJob;
use Modules\Udemy\Jobs\SyncMyCoursesJob;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\Client\UdemySdk;
use Modules\Udemy\Services\CurriculumItems\Lecture;
use Modules\Udemy\Services\CurriculumItems\SimpleQuiz;

class UdemyService
{
    public const string UDEMY_QUEUE_NAME = 'udemy';

    private array $mappingCurriculumItems = [
        'lecture' => Lecture::class,
        'simple-quiz' => SimpleQuiz::class,
    ];

    public function syncMyCourses(UserToken $userToken): void
    {
        /**
         * - subscribedCourses
         * ---- create Course and link with User
         * ------- dispatch UdemyCourseCreated
         * --------- SyncCurriculumItems
         * ---- loop all pages
         */
        SyncMyCoursesJob::dispatch($userToken);
    }

    public function syncCurriculumItems(UserToken $userToken, UdemyCourse $course): void
    {
        SyncCurriculumItemsJob::dispatch($userToken, $course);
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
        UserToken $userToken,
        CurriculumItem $curriculumItem
    ): bool {
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
