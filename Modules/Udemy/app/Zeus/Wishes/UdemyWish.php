<?php

namespace Modules\Udemy\Zeus\Wishes;

use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use JsonException;
use Modules\Core\Zeus\Wishes\FactoryWish;
use Modules\Udemy\Client\Client;
use Modules\Udemy\Client\Dto\CourseCategoryDto;
use Modules\Udemy\Client\Dto\CourseDto;
use Modules\Udemy\Client\Sdk\CoursesApi;
use Modules\Udemy\Client\Sdk\MeApi;
use Modules\Udemy\Models\UserToken;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class UdemyWish extends FactoryWish
{
    private UserToken $userToken;

    public const string ME_SUBSCRIBED_COURSES = 'api-2.0/users/me/subscribed-courses';

    public const int COURSE_ID = 59583;

    public const int LECTURE_ID = 632;

    private function getHeaders(): array
    {
        $token = isset($this->userToken) ? $this->userToken->token : 'testing';

        return [
            'User-Agent' => 'testing',
            'Authorization' => 'Bearer ' . $token,
            'Accept' => Client::CONTENT_TYPE,
        ];
    }

    final public function setToken(UserToken $userToken): self
    {
        $this->userToken = $userToken;

        return $this;
    }

    final public function wishSubscribedCoursesCategories(
        int $pageSize = 15,
        bool $error = false,
        string $respondFile = 'subscribed-courses-categories.json'
    ): self {
        $this->clientMock->allows('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                MeApi::ENDPOINT . '/subscribed-courses-categories',
                [
                    'headers' => $this->getHeaders(),
                    'query' => [
                        'fields' => [
                            'course_category' => implode(',', CourseCategoryDto::FIELDS),
                        ],
                        'previewing' => false,
                        'page_size' => $pageSize,
                        'is_archived' => false,
                    ],
                ]
            )
            ->andReturns(
                $error
                    ? $this->buildResponse(SymfonyResponse::HTTP_FORBIDDEN, Client::CONTENT_TYPE)
                    : $this->buildResponse(SymfonyResponse::HTTP_OK, Client::CONTENT_TYPE, $respondFile)
            );

        return $this;
    }

    final public function wishSubscribedCourses(
        int $pageSize = 100,
        bool $error = false,
        ?string $respondFile = 'subscribed-courses.json'
    ): self {
        $this->clientMock->allows('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                self::ME_SUBSCRIBED_COURSES,
                [
                    'headers' => $this->getHeaders(),
                    'query' => [
                        'fields' => [
                            'course' => implode(',', CourseDto::FIELDS),
                            'users' => '@min,job_title',
                        ],
                        'ordering' => '-last_accessed',
                        'page' => 1,
                        'page_size' => $pageSize,
                        'is_archived' => false,
                    ],
                ]
            )
            ->andReturns(
                $error
                    ? $this->buildResponse(SymfonyResponse::HTTP_FORBIDDEN, Client::CONTENT_TYPE)
                    : $this->buildResponse(SymfonyResponse::HTTP_OK, Client::CONTENT_TYPE, $respondFile)
            );

        return $this;
    }

    final public function wishSubscribedCoursesPaging(): self
    {
        for ($index = 1; $index <= 3; $index++) {
            $this->clientMock->allows('request')
                ->withSomeOfArgs(
                    Request::METHOD_GET,
                    self::ME_SUBSCRIBED_COURSES,
                    [
                        'headers' => $this->getHeaders(),
                        'query' => [
                            'fields' => [
                                'course' => implode(',', CourseDto::FIELDS),
                                'users' => '@min,job_title',
                            ],
                            'ordering' => '-last_accessed',
                            'page' => $index,
                            'page_size' => 100,
                            'is_archived' => false,
                        ],
                    ]
                )->andReturns(
                    $this->buildResponse(SymfonyResponse::HTTP_OK, Client::CONTENT_TYPE, 'subscribed-courses_' . $index . '.json')
                );
        }

        return $this;
    }

    final public function wishSubscriberCurriculumItems(): self
    {
        $this->clientMock->allows('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                CoursesApi::ENDPOINT . '/' . self::COURSE_ID
                . '/subscriber-curriculum-items',
            )->andReturns(
                $this->buildResponse(SymfonyResponse::HTTP_OK, Client::CONTENT_TYPE, 'subscriber-curriculum-items.json')
            );

        return $this;
    }

    /**
     * @throws JsonException
     */
    final public function wishProgress(int $categoryId): self
    {
        $this->clientMock->allows('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                'api-2.0/users/me/subscribed-courses/' . $categoryId . '/progress'
            )
            ->andReturn(
                new Response(
                    SymfonyResponse::HTTP_OK,
                    [
                        'Content-Type' => Client::CONTENT_TYPE,
                    ],
                    json_encode([
                        'completed_lecture_ids' => [1, 2, 3],
                        'completed_quiz_ids' => [4, 5, 6],
                        'completed_assignment_ids' => [7, 8, 9],
                    ], JSON_THROW_ON_ERROR)
                )
            );

        return $this;
    }
}
