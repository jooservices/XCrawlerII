<?php

namespace Modules\Core\Zeus\Wishes;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;
use Modules\Client\Services\Factory;
use Modules\Core\Zeus\AbstractWish;
use ReflectionClass;

class FactoryWish extends AbstractWish
{
    protected MockInterface $clientMock;

    public function __construct()
    {
        parent::__construct();

        if (!isset($this->clientMock)) {
            $this->clientMock = Mockery::mock(ClientInterface::class);
        }
    }

    final public function getClientMock(): MockInterface
    {
        return $this->clientMock;
    }

    final public function setClientMock(Mockery\MockInterface $clientMock): self
    {
        $this->clientMock = $clientMock;

        return $this;
    }

    final public function mock(): void
    {
        $factoryMock = Mockery::mock(Factory::class);
        $factoryMock->allows('enableRetries')
            ->andReturnSelf();
        $factoryMock->allows('make')
            ->andReturns($this->clientMock);

        app()->instance(Factory::class, $factoryMock);
    }

    public function wish(?\Closure $callback = null): bool
    {
        if ($callback) {
            $this->clientMock = $callback($this->getClientMock());
        }

        $this->mock();

        return true;
    }

    protected function buildResponse(
        int $statusCode,
        string $contentType,
        ?string $bodyFile = null,
    ): Response {
        if ($bodyFile === null) {
            return new Response(
                $statusCode,
                [
                    'Content-Type' => $contentType,
                ],
                null
            );
        }

        $classInfo = new ReflectionClass($this);
        $dirName = dirname($classInfo->getFileName());

        $filePath = $dirName . '/../Fixtures/' . $bodyFile;

        if (file_exists($filePath)) {
            $bodyFile = file_get_contents($filePath);
        }

        return new Response(
            $statusCode,
            [
                'Content-Type' => $contentType,
            ],
            $bodyFile
        );
    }
}
