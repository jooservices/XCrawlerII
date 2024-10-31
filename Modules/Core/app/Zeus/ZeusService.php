<?php

namespace Modules\Core\Zeus;

use GuzzleHttp\ClientInterface;
use Mockery;
use Modules\Client\Services\Factory;
use Modules\Core\Interfaces\IWish;

class ZeusService
{
    private Mockery\MockInterface $clientMock;

    public function __construct()
    {
        $this->clientMock = Mockery::mock(ClientInterface::class);
    }

    public function getClientMock(): Mockery\MockInterface
    {
        return $this->clientMock;
    }

    public function setClientMock(Mockery\MockInterface $clientMock): self
    {
        $this->clientMock = $clientMock;

        return $this;
    }

    public function apply()
    {
        $factoryMock = Mockery::mock(Factory::class);
        $factoryMock->shouldReceive('enableRetries')
            ->andReturnSelf();
        $factoryMock->shouldReceive('make')
            ->andReturn($this->clientMock);

        app()->instance(Factory::class, $factoryMock);
    }

    /**
     * @throws \Exception
     */
    public function wish(string $wish): self
    {
        if (!class_exists($wish)) {
            throw new \Exception("Wish class $wish does not exist");
        }

        $this->clientMock = app($wish)->wish($this->clientMock);
        $this->apply();

        return $this;
    }
}
