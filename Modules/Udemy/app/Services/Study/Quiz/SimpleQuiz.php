<?php

namespace Modules\Udemy\Services\Study\Quiz;

use Modules\Udemy\Services\Study\BaseStudyChild;

class SimpleQuiz extends BaseStudyChild
{
    final public function study(): void
    {
        /**
         * @TODO Check if attempted already
         */
        // Attempt created
        $attemptId = $this->sdk->me()->userAttemptedQuizzes($this->curriculumItem);

        if ($attemptId === false) {
            return;
        }

        $assessmentsDto = $this->sdk->quizzes()->assessments($this->curriculumItem);

        if (!$assessmentsDto) {
            return;
        }

        foreach ($assessmentsDto->getResults() as $assessment) {
            $this->sdk->me()->assessmentAnswers(
                $this->curriculumItem,
                $attemptId,
                $assessment
            );
        }
    }
}
