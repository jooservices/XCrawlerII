<?php

namespace Modules\Core\Zeus;

use Closure;
use GuzzleHttp\ClientInterface;
use Mockery;
use Modules\Client\Services\Factory;
use Modules\Core\Exceptions\ClassNotFoundException;

class ZeusService
{
    private Mockery\MockInterface $clientMock;

    public function __construct()
    {
        $this->clientMock = Mockery::mock(ClientInterface::class);
    }

    final public function getClientMock(): Mockery\MockInterface
    {
        return $this->clientMock;
    }

    final public function setClientMock(Mockery\MockInterface $clientMock): self
    {
        $this->clientMock = $clientMock;

        return $this;
    }

    final public function apply(): void
    {
        $factoryMock = Mockery::mock(Factory::class);
        $factoryMock->allows('enableRetries')
            ->andReturnSelf();
        $factoryMock->allows('make')
            ->andReturns($this->clientMock);

        app()->instance(Factory::class, $factoryMock);
    }

    /**
     * @throws \Exception
     */
    final public function wish(
        string $wish,
        ?Closure $callback = null
    ): self {
        Mockery::close();
        if (!class_exists($wish)) {
            throw new ClassNotFoundException("Wish class $wish does not exist");
        }

        $this->clientMock = app($wish)->wish($this->clientMock);

        if ($callback) {
            $this->clientMock = $callback($this->clientMock);
        }

        $this->apply();

        return $this;
    }
}
