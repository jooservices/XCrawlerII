<?php

namespace Modules\Udemy\Services\Client\Sdk;

use Exception;
use Modules\Client\Interfaces\IClient;
use Modules\Client\Services\ClientManager;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\Client\Client;
use Modules\Udemy\Services\Client\Entities\AssessmentEntity;
use Modules\Udemy\Services\Client\Entities\CoursesCategoriesEntity;
use Modules\Udemy\Services\Client\Entities\CoursesEntity;
use Modules\Udemy\Services\Client\Entities\ProgressEntity;
use Symfony\Component\HttpFoundation\Request;

class MeApi
{
    /**
     * @var Client
     */
    private IClient $client;

    private const string ENDPOINT = 'api-2.0/users/me';

    public function __construct(private readonly ClientManager $manager)
    {
        $this->client = $this->manager->getClient(Client::class);
    }

    /**
     * @throws Exception
     */
    public function subscribedCoursesCategories(
        string $token,
        array $payload = []
    ): ?CoursesCategoriesEntity {
        $this->client->setToken($token);
        $payload = array_merge(
            [
                'fields' => [
                    'course_category' => 'id,title',
                ],
                'previewing' => false,
                'page_size' => 15,
                'is_archived' => false,
            ],
            $payload
        );

        $response = $this->client->request(
            Request::METHOD_GET,
            self::ENDPOINT . '/subscribed-courses-categories',
            $payload
        );

        if (!$response->isSuccess()) {
            return null;
        }

        return new CoursesCategoriesEntity($response->parseBody()->getData());
    }

    /**
     * @throws Exception
     */
    public function subscribedCourses(
        string $token,
        array $payload = []
    ): ?CoursesEntity {
        $this->client->setToken($token);
        $payload = array_merge(
            [
                'fields' => [
                    'course' => 'archive_time,buyable_object_type,completion_ratio,enrollment_time,favorite_time,features,image_240x135,image_480x270,is_practice_test_course,is_private,is_published,last_accessed_time,num_collections,published_title,title,tracking_id,url,visible_instructors,is_course_available_in_org',
                    'users' => '@min,job_title',
                ],
                'ordering' => '-last_accessed',
                'page' => 1,
                'page_size' => 50,
                'is_archived' => false,
            ],
            $payload
        );

        $response = $this->client->request(
            Request::METHOD_GET,
            self::ENDPOINT . '/subscribed-courses',
            $payload
        );

        if (!$response->isSuccess()) {
            return null;
        }

        return new CoursesEntity($response->parseBody()->getData());
    }

    public function progress(
        string $token,
        int $courseId,
        array $payload = []
    )
    {
        $this->client->setToken($token);
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
            self::ENDPOINT . '/subscribed-courses/' . $courseId . '/progress',
            $payload
        );

        if (!$response->isSuccess()) {
            return null;
        }

        return new ProgressEntity($response->parseBody()->getData());
    }

    /**
     * @throws Exception
     */
    public function lectureProgressLogs(
        UserToken $userToken,
        CurriculumItem $curriculumItem,
        array $payload = []
    ): bool {
        $this->client->setToken($userToken->token);
        $response = $this->client->request(
            Request::METHOD_POST,
            self::ENDPOINT
            . '/subscribed-courses'
            . '/' . $curriculumItem->course->id
            . '/lectures/' . $curriculumItem->id
            . '/progress-logs',
            $payload
        );

        return $response->getStatusCode() === 200;
    }

    public function completedLectures(
        UserToken $userToken,
        CurriculumItem $curriculumItem,
    ): bool {
        $this->client->setToken($userToken->token);
        $response = $this->client->request(
            Request::METHOD_POST,
            self::ENDPOINT
            . '/subscribed-courses'
            . '/' . $curriculumItem->course->id
            . '/completed-lectures',
            [
                'downloaded' => false,
                'lecture_id' => $curriculumItem->id,
            ]
        );

        return $response->getStatusCode() === 201;
    }

    public function viewLogs(
        UserToken $userToken,
        CurriculumItem $curriculumItem,
    ): bool {
        $this->client->setToken($userToken->token);
        $response = $this->client->request(
            Request::METHOD_POST,
            self::ENDPOINT
            . '/subscribed-courses'
            . '/' . $curriculumItem->course->id
            . '/lectures/' . $curriculumItem->id
            . '/view-logs',
        );

        return $response->getStatusCode() === 201;
    }

    public function userAttemptedQuizzes(
        string $token,
        CurriculumItem $curriculumItem,
    ): mixed {
        $this->client->setToken($token);
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
        string $token,
        CurriculumItem $curriculumItem,
        int $attempt,
        AssessmentEntity $assessmentEntity,
    ) {
        $this->client->setToken($token);
        /**
         * @TODO Handle duration
         */
        $response = $this->client->request(
            Request::METHOD_POST,
            self::ENDPOINT
            . '/subscribed-courses'
            . '/' . $curriculumItem->course->id
            . '/user-attempted-quizzes/' . $attempt
            . '/assessment-answers/?fields[user_answers_assessment]=id,response,assessment,is_marked_for_review,score',
            [
                'assessment_id' => $assessmentEntity->getId(),
                'duration' => 5,
                'response' => json_encode($assessmentEntity->getCorrectResponse()),
            ]
        );

        if ($response->getStatusCode() !== 201) {
            return false;
        }

        return $response->parseBody()->getData();
    }
}
