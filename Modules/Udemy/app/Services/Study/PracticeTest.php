<?php

namespace Modules\Udemy\Services\Study;

use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\Study\Interfaces\IStudy;

class PracticeTest implements IStudy
{
    public function study(UserToken $userToken, CurriculumItem $curriculumItem): void
    {
        $sdk = app(UdemySdk::class)
            ->setToken($userToken);

        /**
         * @TODO Check if attempted already
         */
        // Attempt created
        $attemptId = $sdk->me()->userAttemptedQuizzes($curriculumItem);

        if ($attemptId === false) {
            return;
        }

        $assessmentsDto = $sdk->quizzes()->assessments($curriculumItem);

        foreach ($assessmentsDto->getResults() as $assessment) {
            $sdk->me()->assessmentAnswers(
                $curriculumItem,
                $attemptId,
                $assessment
            );
        }
    }
}
