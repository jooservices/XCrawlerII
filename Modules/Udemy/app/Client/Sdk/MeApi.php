<?php

namespace Modules\Udemy\Client\Sdk;

use Exception;
use Modules\Udemy\Client\Dto\AssessmentDto;
use Modules\Udemy\Client\Dto\AssessmentsDto;
use Modules\Udemy\Client\Dto\CourseCategoriesDto;
use Modules\Udemy\Client\Dto\CourseCategoryDto;
use Modules\Udemy\Client\Dto\CourseProgressDto;
use Modules\Udemy\Client\Dto\CoursesDto;
use Modules\Udemy\Client\Entities\AssessmentEntity;
use Modules\Udemy\Client\Entities\CoursesCategoriesEntity;
use Modules\Udemy\Client\Entities\CoursesEntity;
use Modules\Udemy\Client\Entities\ProgressEntity;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Symfony\Component\HttpFoundation\Request;

class MeApi extends AbstractApi
{
    private const string ENDPOINT = 'api-2.0/users/me';

    /**
     * @throws Exception
     */
    public function subscribedCoursesCategories(
        array $payload = []
    ): ?CourseCategoriesDto {
        $response = $this->client->request(
            Request::METHOD_GET,
            $this->getEndpoint('subscribed-courses-categories'),
            array_merge(CourseCategoriesDto::getPayload(), $payload)
        );

        return (new CourseCategoriesDto())->transform($response);
    }

    /**
     * @throws Exception
     */
    public function subscribedCourses(
        array $payload = []
    ): ?CoursesDto {
        $response = $this->client->request(
            Request::METHOD_GET,
            $this->getEndpoint('subscribed-courses'),
            array_merge(CoursesDto::getPayload(), $payload)
        );

        return (new CoursesDto())->transform($response);
    }

    public function progress(
        int $courseId,
        array $payload = []
    ): ?CourseProgressDto {
        $payload = array_merge(
            [
                'fields' => [
                    'course' => 'completed_lecture_ids,completed_quiz_ids,last_seen_page,completed_assignment_ids,first_completion_time',
                ],
                'page' => 1,
                'page_size' => 100,
            ],
            $payload
        );

        $response = $this->client->request(
            Request::METHOD_GET,
            $this->getEndpoint('subscribed-courses/' . $courseId . '/progress'),
            $payload
        );

        if (!$response->isSuccess()) {
            return null;
        }

        return (new CourseProgressDto())->transform($response->parseBody()->getData());
    }

    /**
     * @throws Exception
     */
    public function lectureProgressLogs(
        CurriculumItem $curriculumItem,
        array $payload = []
    ): bool {
        $response = $this->client->request(
            Request::METHOD_POST,
            $this->getEndpoint(
                '/subscribed-courses'
                . '/' . $curriculumItem->course->id
                . '/lectures/' . $curriculumItem->id
                . '/progress-logs'
            ),
            $payload
        );

        return $response->getStatusCode() === 200;
    }

    public function completedLectures(
        CurriculumItem $curriculumItem,
    ): bool {
        $response = $this->client->request(
            Request::METHOD_POST,
            $this->getEndpoint(
                '/subscribed-courses'
                . '/' . $curriculumItem->course->id
                . '/completed-lectures'
            ),
            [
                'downloaded' => false,
                'lecture_id' => $curriculumItem->id,
            ]
        );

        return $response->getStatusCode() === 201;
    }

    public function viewLogs(
        CurriculumItem $curriculumItem,
    ): bool {
        $response = $this->client->request(
            Request::METHOD_POST,
            $this->getEndpoint(
                '/subscribed-courses'
                . '/' . $curriculumItem->course->id
                . '/lectures/' . $curriculumItem->id
                . '/view-logs'
            )
        );

        return $response->getStatusCode() === 201;
    }

    public function userAttemptedQuizzes(
        CurriculumItem $curriculumItem,
    ): mixed {
        $response = $this->client->request(
            Request::METHOD_POST,
            self::ENDPOINT
            . '/subscribed-courses'
            . '/' . $curriculumItem->course->id
            . '/quizzes/' . $curriculumItem->id
            . '/user-attempted-quizzes/?fields[user_attempted_quiz]=id,created,viewed_time,completion_time,version,completed_assessments,results_summary',
            [
                'is_viewed' => true,
                'version' => 1,
            ]
        );

        if ($response->getStatusCode() !== 201) {
            return false;
        }

        return $response->parseBody()->getData()->id;
    }

    public function assessmentAnswers(
        CurriculumItem $curriculumItem,
        int $attempt,
        AssessmentDto $assessmentDto,
    ) {
        /**
         * @TODO Handle duration
         */
        $response = $this->client->request(
            Request::METHOD_POST,
            $this->getEndpoint(
                '/subscribed-courses'
                . '/' . $curriculumItem->course->id
                . '/user-attempted-quizzes/' . $attempt
                . '/assessment-answers/?fields[user_answers_assessment]=id,response,assessment,is_marked_for_review,score'
            ),
            [
                'assessment_id' => $assessmentDto->getId(),
                'duration' => 5,
                'response' => json_encode($assessmentDto->getCorrectResponse()),
            ]
        );

        if ($response->getStatusCode() !== 201) {
            return false;
        }

        return $response->parseBody()->getData();
    }

    protected function getEndpoint(string $path): string
    {
        return self::ENDPOINT . '/' . trim($path, '/');
    }
}
