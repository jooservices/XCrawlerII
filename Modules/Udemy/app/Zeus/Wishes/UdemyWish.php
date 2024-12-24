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

    public function wish(
        MockInterface $clientMock
    ): MockInterface {
        $clientMock = $this->subscribedCoursesCategories($clientMock);
        $clientMock = $this->subscribedCourses($clientMock);
        $clientMock->shouldReceive('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                'api-2.0/courses/59583/subscriber-curriculum-items'
            )
            ->andReturn(
                $this->buildResponse(SymfonyResponse::HTTP_OK, 'subscriber-curriculum-items')
            );

        return $clientMock;
    }

    private function subscribedCoursesCategories(MockInterface $clientMock): MockInterface
    {
        $clientMock->shouldReceive('request')
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
            ->andReturn(
                $this->buildResponse(SymfonyResponse::HTTP_OK, 'subscribed-courses-categories')
            );

        $clientMock->shouldReceive('request')
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
            ->andReturn(
                $this->buildResponse(SymfonyResponse::HTTP_FORBIDDEN),
            );

        return $clientMock;
    }

    private function subscribedCourses(MockInterface $clientMock): MockInterface
    {
        $clientMock->shouldReceive('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                'api-2.0/users/me/subscribed-courses',
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
            ->andReturn(
                $this->buildResponse(SymfonyResponse::HTTP_OK, 'subscribed-courses')
            );
        $clientMock->shouldReceive('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                'api-2.0/users/me/subscribed-courses',
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
            ->andReturn(
                $this->buildResponse(SymfonyResponse::HTTP_FORBIDDEN),
            );

        for ($index = 1; $index <= 3; $index++) {
            $clientMock->shouldReceive('request')
                ->withSomeOfArgs(
                    Request::METHOD_GET,
                    'api-2.0/users/me/subscribed-courses',
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
                )->andReturn(
                    $this->buildResponse(SymfonyResponse::HTTP_OK, 'subscribed-courses_' . $index)
                );
        }

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
