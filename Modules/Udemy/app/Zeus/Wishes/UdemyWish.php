<?php

namespace Modules\Udemy\Zeus\Wishes;

use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Modules\Core\Zeus\AbstractWish;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class UdemyWish extends AbstractWish
{
    public const string CONTENT_TYPE = 'application/json, text/plain';

    private const array HEADERS = [
        'User-Agent' => 'testing',
        'Authorization' => 'Bearer testing',
        'Accept' => self::CONTENT_TYPE,
    ];

    public function wish(MockInterface $clientMock): MockInterface
    {
        $clientMock = $this->subscribedCoursesCategories($clientMock);

        $clientMock->shouldReceive('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                'api-2.0/users/me/subscribed-courses',
                [
                    'headers' => self::HEADERS,
                    'query' => [
                        'fields' => [
                            'course' => 'archive_time,buyable_object_type,completion_ratio,enrollment_time,favorite_time,features,image_240x135,image_480x270,is_practice_test_course,is_private,is_published,last_accessed_time,num_collections,published_title,title,tracking_id,url,visible_instructors,is_course_available_in_org',
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
                new Response(
                    SymfonyResponse::HTTP_OK,
                    [
                        'Content-Type' => self::CONTENT_TYPE,
                    ],
                    file_get_contents(__DIR__ . '/../Fixtures/subscribed-courses.json')
                )
            );
        $clientMock->shouldReceive('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                'api-2.0/users/me/subscribed-courses',
                [
                    'headers' => self::HEADERS,
                    'query' => [
                        'fields' => [
                            'course' => 'archive_time,buyable_object_type,completion_ratio,enrollment_time,favorite_time,features,image_240x135,image_480x270,is_practice_test_course,is_private,is_published,last_accessed_time,num_collections,published_title,title,tracking_id,url,visible_instructors,is_course_available_in_org',
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
                new Response(
                    SymfonyResponse::HTTP_FORBIDDEN,
                    [
                        'Content-Type' => self::CONTENT_TYPE,
                    ],
                    ''
                )
            );

        for($index = 1; $index <= 3; $index++) {
            $clientMock->shouldReceive('request')
                ->withSomeOfArgs(
                    Request::METHOD_GET,
                    'api-2.0/users/me/subscribed-courses',
                    [
                        'headers' => self::HEADERS,
                        'query' => [
                            'fields' => [
                                'course' => 'archive_time,buyable_object_type,completion_ratio,enrollment_time,favorite_time,features,image_240x135,image_480x270,is_practice_test_course,is_private,is_published,last_accessed_time,num_collections,published_title,title,tracking_id,url,visible_instructors,is_course_available_in_org',
                                'users' => '@min,job_title',
                            ],
                            'ordering' => '-last_accessed',
                            'page' => $index,
                            'page_size' => 40,
                            'is_archived' => false,
                        ],
                    ]
                )
                ->andReturn(
                    new Response(
                        SymfonyResponse::HTTP_OK,
                        [
                            'Content-Type' => self::CONTENT_TYPE,
                        ],
                        file_get_contents(__DIR__ . '/../Fixtures/subscribed-courses_' . $index. '.json')
                    )
                );
        }

        $clientMock->shouldReceive('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                'api-2.0/courses/59583/subscriber-curriculum-items'
            )
            ->andReturn(
                new Response(
                    SymfonyResponse::HTTP_OK,
                    [
                        'Content-Type' => self::CONTENT_TYPE,
                    ],
                    file_get_contents(__DIR__ . '/../Fixtures/subscriber-curriculum-items.json')
                )
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
                            'course_category' => 'id,title',
                        ],
                        'previewing' => false,
                        'page_size' => 15,
                        'is_archived' => false,
                    ],
                ]
            )
            ->andReturn(
                new Response(
                    SymfonyResponse::HTTP_OK,
                    [
                        'Content-Type' => self::CONTENT_TYPE,
                    ],
                    file_get_contents(__DIR__ . '/../Fixtures/subscribed-courses-categories.json')
                )
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
                new Response(
                    SymfonyResponse::HTTP_FORBIDDEN,
                    [
                        'Content-Type' => self::CONTENT_TYPE,
                    ],
                    ''
                )
            );

        return $clientMock;
    }
}
