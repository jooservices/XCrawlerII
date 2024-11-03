<?php

namespace Modules\Udemy\Services\CurriculumItems;

use Modules\Udemy\Interfaces\IStudyCurriculum;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\Client\UdemySdk;

class SimpleQuiz implements IStudyCurriculum
{
    public function process(UserToken $userToken, CurriculumItem $curriculumItem)
    {
        $sdk = app(UdemySdk::class);
        // Attempt created
        $attempt = $sdk->me()->userAttemptedQuizzes($userToken->token, $curriculumItem);

        if ($attempt !== false) {
            $assessments = ($sdk->quizzes()->assessments(
                $userToken->token,
                $curriculumItem
            ));

            foreach ($assessments->getResults() as $assessment) {
                $sdk->me()->assessmentAnswers(
                    $userToken->token,
                    $curriculumItem,
                    $attempt,
                    $assessment
                );
            }
        }
    }
}
