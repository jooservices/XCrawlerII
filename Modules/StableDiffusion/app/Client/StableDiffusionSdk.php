<?php

namespace Modules\StableDiffusion\Client;

use Modules\Client\Interfaces\IClient;
use Modules\Client\Interfaces\IResponse;
use Modules\Client\Services\ClientManager;
use Modules\StableDiffusion\Client\DTO\Text2ImageDto;

class StableDiffusionSdk
{
    /**
     * @var Client
     */
    private IClient $client;

    public function __construct(private readonly ClientManager $manager)
    {
        $this->client = $this->manager->getClient(Client::class);
    }

    public function txt2img(
        Text2ImageDto $entity,
    ): IResponse {

        return $this->client->post(
            '/sdapi/v1/txt2img',
            $entity->toArray()
        );
    }

    public function queueStatus()
    {
        return $this->client->get('queue/status');
    }

    public function progress()
    {
        return $this->client->get('sdapi/v1/progress');
    }
}
