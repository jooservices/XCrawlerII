<?php

namespace Modules\Udemy\Zeus\Wishes;

use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Modules\Core\Zeus\AbstractWish;
use Modules\Udemy\Client\Client;
use Modules\Udemy\Client\Dto\CourseCategoryDto;
use Modules\Udemy\Client\Dto\CourseDto;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class UdemyWish extends AbstractWish
{
    private const array HEADERS = [
        'User-Agent' => 'testing',
        'Authorization' => 'Bearer testing',
        'Accept' => Client::CONTENT_TYPE,
    ];

    private const string ME_SUBSCRIBED_COURSES = 'api-2.0/users/me/subscribed-courses';

    public const COURSE_ID = 59583;

    public const LECTURE_ID = 632;

    final public function wish(
        MockInterface $clientMock,
    ): MockInterface {
        $clientMock = $this->subscribedCoursesCategories($clientMock);
        $clientMock = $this->subscribedCourses($clientMock);
        $clientMock->allows('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                'api-2.0/courses/59583/subscriber-curriculum-items'
            )
            ->andReturns(
                $this->buildResponse(SymfonyResponse::HTTP_OK, 'subscriber-curriculum-items')
            );
        $clientMock->allows('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                'api-2.0/courses/59583/progress'
            )
            ->andReturn([
                'completed_lecture_ids' => [1, 2, 3],
                'completed_quiz_ids' => [4, 5, 6],
                'completed_assignment_ids' => [7, 8, 9],
            ]);

        return $clientMock;
    }

    private function subscribedCoursesCategories(MockInterface $clientMock): MockInterface
    {
        $clientMock->allows('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                'api-2.0/users/me/subscribed-courses-categories',
                [
                    'headers' => self::HEADERS,
                    'query' => [
                        'fields' => [
                            'course_category' => implode(',', CourseCategoryDto::FIELDS),
                        ],
                        'previewing' => false,
                        'page_size' => 15,
                        'is_archived' => false,
                    ],
                ]
            )
            ->andReturns(
                $this->buildResponse(SymfonyResponse::HTTP_OK, 'subscribed-courses-categories')
            );

        $clientMock->allows('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                'api-2.0/users/me/subscribed-courses-categories',
                [
                    'headers' => self::HEADERS,
                    'query' => [
                        'fields' => [
                            'course_category' => 'id,title',
                        ],
                        'previewing' => false,
                        'page_size' => 15,
                        'is_archived' => false,
                        'error' => 403,
                    ],
                ]
            )
            ->andReturns(
                $this->buildResponse(SymfonyResponse::HTTP_FORBIDDEN),
            );

        return $clientMock;
    }

    private function subscribedCourses(MockInterface $clientMock): MockInterface
    {
        $clientMock->allows('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                self::ME_SUBSCRIBED_COURSES,
                [
                    'headers' => self::HEADERS,
                    'query' => [
                        'fields' => [
                            'course' => implode(',', CourseDto::FIELDS),
                            'users' => '@min,job_title',
                        ],
                        'ordering' => '-last_accessed',
                        'page' => 1,
                        'page_size' => 100,
                        'is_archived' => false,
                    ],
                ]
            )
            ->andReturns(
                $this->buildResponse(SymfonyResponse::HTTP_OK, 'subscribed-courses')
            );
        $clientMock->allows('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                self::ME_SUBSCRIBED_COURSES,
                [
                    'headers' => self::HEADERS,
                    'query' => [
                        'fields' => [
                            'course' => implode(',', CourseDto::FIELDS),
                            'users' => '@min,job_title',
                        ],
                        'ordering' => '-last_accessed',
                        'page' => 1,
                        'page_size' => 100,
                        'is_archived' => false,
                        'error' => 403,
                    ],
                ]
            )
            ->andReturns(
                $this->buildResponse(SymfonyResponse::HTTP_FORBIDDEN),
            );

        for ($index = 1; $index <= 3; $index++) {
            $clientMock->allows('request')
                ->withSomeOfArgs(
                    Request::METHOD_GET,
                    self::ME_SUBSCRIBED_COURSES,
                    [
                        'headers' => self::HEADERS,
                        'query' => [
                            'fields' => [
                                'course' => implode(',', CourseDto::FIELDS),
                                'users' => '@min,job_title',
                            ],
                            'ordering' => '-last_accessed',
                            'page' => $index,
                            'page_size' => $index === 1 ? 40 : 100,
                            'is_archived' => false,
                        ],
                    ]
                )->andReturns(
                    $this->buildResponse(SymfonyResponse::HTTP_OK, 'subscribed-courses_' . $index)
                );
        }

        $clientMock->allows('request')
            ->withSomeOfArgs(
                Request::METHOD_POST,
                self::ME_SUBSCRIBED_COURSES
                . self::COURSE_ID . '/lectures/'
                . self::LECTURE_ID . '/view-logs',
            )
            ->andReturns(true);

        return $clientMock;
    }

    private function buildResponse(int $statusCode, ?string $bodyFile = null): Response
    {
        return new Response(
            $statusCode,
            [
                'Content-Type' => Client::CONTENT_TYPE,
            ],
            $bodyFile
                ? file_get_contents(__DIR__ . '/../Fixtures/' . $bodyFile . '.json')
                : null
        );
    }
}
