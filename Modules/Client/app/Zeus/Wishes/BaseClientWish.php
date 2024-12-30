<?php

namespace Modules\Client\Zeus\Wishes;

use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Modules\Core\Zeus\Wishes\FactoryWish;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class BaseClientWish extends FactoryWish
{
    final public function wishJson(): self
    {
        $this->clientMock->allows('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                '/json'
            )
            ->andReturns(
                new Response(
                    SymfonyResponse::HTTP_OK,
                    [
                        'Content-Type' => 'application/json',
                    ],
                    json_encode(['json'])
                )
            );

        return $this;
    }

    final public function wishHtml(): self
    {
        $this->clientMock->allows('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                '/html'
            )
            ->andReturns(
                new Response(
                    SymfonyResponse::HTTP_OK,
                    [
                        'Content-Type' => 'text/html',
                    ],
                    $this->faker->randomHtml
                )
            );

        return $this;
    }
}
