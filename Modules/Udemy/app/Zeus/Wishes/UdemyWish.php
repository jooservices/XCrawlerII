<?php

namespace Modules\Udemy\Zeus\Wishes;

use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Modules\Core\Zeus\AbstractWish;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class UdemyWish extends AbstractWish
{
    public const string CONTENT_TYPE = 'application/json';

    public function wish(MockInterface $clientMock): MockInterface
    {
        $clientMock->shouldReceive('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                'api-2.0/users/me/subscribed-courses-categories'
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
                'api-2.0/users/me/subscribed-courses'
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
}
