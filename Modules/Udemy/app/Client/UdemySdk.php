<?php

namespace Modules\Udemy\Client;

use Illuminate\Contracts\Container\BindingResolutionException;
use Modules\Client\Interfaces\IClient;
use Modules\Client\Services\ClientManager;
use Modules\Udemy\Client\Sdk\CoursesApi;
use Modules\Udemy\Client\Sdk\MeApi;
use Modules\Udemy\Client\Sdk\QuizzesApi;
use Modules\Udemy\Client\Traits\TMe;
use Modules\Udemy\Models\UserToken;

class UdemySdk
{
    use TMe;

    /**
     * @var Client
     */
    private IClient $client;

    public function __construct(private readonly ClientManager $manager)
    {
        $this->client = $this->manager->getClient(Client::class);
    }

    public function setToken(UserToken $userToken): self
    {
        $this->client->setToken($userToken->token);

        return $this;
    }

    /**
     * @throws BindingResolutionException
     */
    public function me(): MeApi
    {
        return app()->makeWith(MeApi::class, ['client' => $this->client]);
    }

    /**
     * @throws BindingResolutionException
     */
    public function courses(): CoursesApi
    {
        return app()->makeWith(CoursesApi::class, ['client' => $this->client]);
    }

    /**
     * @throws BindingResolutionException
     */
    public function quizzes(): QuizzesApi
    {
        return app()->makeWith(QuizzesApi::class, ['client' => $this->client]);
    }
}
