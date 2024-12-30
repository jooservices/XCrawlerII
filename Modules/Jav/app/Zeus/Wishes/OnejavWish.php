<?php

namespace Modules\Jav\Zeus\Wishes;

use Carbon\Carbon;
use Modules\Core\Zeus\Wishes\FactoryWish;
use Modules\Jav\Client\Onejav\CrawlingService;
use Symfony\Component\HttpFoundation\Request as RequestAlias;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class OnejavWish extends FactoryWish
{
    public const string CONTENT_TYPE = 'text/html';

    final public function wishNew(string $response = 'Onejav/new_1.html'): self
    {
        $this->clientMock->allows('request')
            ->withSomeOfArgs(
                RequestAlias::METHOD_GET,
                'new'
            )
            ->andReturn(
                $this->buildResponse(
                    SymfonyResponse::HTTP_OK,
                    self::CONTENT_TYPE,
                    $response
                )
            );

        return $this;
    }

    final public function wishPopular(string $response = 'Onejav/popular_1.html'): self
    {
        $this->clientMock->allows('request')
            ->withSomeOfArgs(
                RequestAlias::METHOD_GET,
                'popular'
            )
            ->andReturn(
                $this->buildResponse(
                    SymfonyResponse::HTTP_OK,
                    self::CONTENT_TYPE,
                    $response
                )
            );

        return $this;
    }

    final public function wishItem(string $response = 'Onejav/item_1.html'): self
    {
        $this->clientMock->allows('request')
            ->withSomeOfArgs(
                RequestAlias::METHOD_GET,
                'item'
            )
            ->andReturn(
                $this->buildResponse(
                    SymfonyResponse::HTTP_OK,
                    self::CONTENT_TYPE,
                    $response
                )
            );

        return $this;
    }

    final public function wishTags(string $response = 'Onejav/tag.html'): self
    {
        $this->clientMock->allows('request')
            ->withSomeOfArgs(
                RequestAlias::METHOD_GET,
                'tag'
            )
            ->andReturn(
                $this->buildResponse(
                    SymfonyResponse::HTTP_OK,
                    self::CONTENT_TYPE,
                    $response
                )
            );

        return $this;
    }

    final public function wishDaily(): self
    {
        for ($index = 1; $index <= 2; $index++) {
            $this->clientMock->allows('request')
                ->withSomeOfArgs(
                    RequestAlias::METHOD_GET,
                    Carbon::now()->format(CrawlingService::DEFAULT_DATE_FORMAT),
                    ['query' => ['page' => $index]]
                )
                ->andReturn(
                    $this->buildResponse(
                        SymfonyResponse::HTTP_OK,
                        self::CONTENT_TYPE,
                        'Onejav/daily_' . $index . '.html'
                    )
                );
        }

        return $this;
    }
}
