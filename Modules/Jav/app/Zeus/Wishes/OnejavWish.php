<?php

namespace Modules\Jav\Zeus\Wishes;

use Carbon\Carbon;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Modules\Core\Zeus\AbstractWish;
use Modules\Jav\Services\Onejav\CrawlingService;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class OnejavWish extends AbstractWish
{
    public const string CONTENT_TYPE = 'text/html';

    public function wish(MockInterface $clientMock): MockInterface
    {
        foreach (['new', 'popular'] as $endpoint) {
            $clientMock = \Mockery::mock(ClientInterface::class);
            $clientMock->shouldReceive('request')
                ->withSomeOfArgs(
                    Request::METHOD_GET,
                    'new'
                )
                ->andReturn(
                    new Response(
                        SymfonyResponse::HTTP_OK,
                        [
                            'Content-Type' => self::CONTENT_TYPE,
                        ],
                        file_get_contents(__DIR__ . '/../Fixtures/Onejav/' . $endpoint . '_1.html')
                    )
                );
        }

        $clientMock->shouldReceive('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                '404'
            )
            ->andReturn(
                new Response(
                    SymfonyResponse::HTTP_NOT_FOUND,
                    [
                        'Content-Type' => self::CONTENT_TYPE,
                    ],
                    'Not Found'
                )
            );

        $clientMock->shouldReceive('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                '/item'
            )
            ->andReturn(
                new Response(
                    SymfonyResponse::HTTP_OK,
                    [
                        'Content-Type' => self::CONTENT_TYPE,
                    ],
                    file_get_contents(__DIR__ . '/../Fixtures/Onejav/item_1.html')
                )
            );

        for ($index = 1; $index <= 2; $index++) {
            $clientMock->shouldReceive('request')
                ->withSomeOfArgs(
                    Request::METHOD_GET,
                    Carbon::now()->format(CrawlingService::DEFAULT_DATE_FORMAT),
                    [
                        'query' => ['page' => $index],
                    ]
                )
                ->andReturn(
                    new Response(
                        SymfonyResponse::HTTP_OK,
                        [
                            'Content-Type' => self::CONTENT_TYPE,
                        ],
                        file_get_contents(__DIR__ . '/../Fixtures/Onejav/daily_' . $index . '.html')
                    )
                );
        }

        return $clientMock;
    }
}
