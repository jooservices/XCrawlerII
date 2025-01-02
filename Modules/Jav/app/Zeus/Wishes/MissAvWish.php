<?php

namespace Modules\Jav\Zeus\Wishes;

use Modules\Core\Zeus\Wishes\FactoryWish;
use Symfony\Component\HttpFoundation\Request as RequestAlias;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class MissAvWish extends FactoryWish
{
    public const string CONTENT_TYPE = 'text/html';

    final public function wishRecentUpdate(string $response = 'MissAv/recent_update_1.html'): self
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

    final public function wishDetail(string $response = 'MissAv/mmraa-336.html'): self
    {
        $this->clientMock->allows('request')
            ->withSomeOfArgs(
                RequestAlias::METHOD_GET,
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
}
