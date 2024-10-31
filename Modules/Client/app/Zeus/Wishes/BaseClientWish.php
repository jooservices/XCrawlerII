<?php

namespace Modules\Client\Zeus\Wishes;

use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Modules\Core\Zeus\AbstractWish;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class BaseClientWish extends AbstractWish
{
    public function wish(MockInterface $clientMock): MockInterface
    {
        $clientMock = $this->json($clientMock);
        $clientMock = $this->html($clientMock);

        return $clientMock;
    }

    private function json(MockInterface $clientMock): MockInterface
    {
        $clientMock->shouldReceive('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                '/json'
            )
            ->andReturn(
                new Response(
                    SymfonyResponse::HTTP_OK,
                    [
                        'Content-Type' => 'application/json',
                    ],
                    json_encode(['json'])
                )
            );

        return $clientMock;
    }

    public function html(MockInterface $clientMock): MockInterface
    {
        $clientMock->shouldReceive('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                '/html'
            )
            ->andReturn(
                new Response(
                    SymfonyResponse::HTTP_OK,
                    [
                        'Content-Type' => 'text/html',
                    ],
                    $this->faker->randomHtml
                )
            );

        return $clientMock;
    }
}
