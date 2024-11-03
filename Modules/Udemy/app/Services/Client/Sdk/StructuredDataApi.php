<?php

namespace Modules\Udemy\Services\Client\Sdk;

use Modules\Client\Interfaces\IClient;
use Modules\Client\Services\ClientManager;
use Modules\Udemy\Services\Client\Client;

use Modules\Udemy\Services\Client\Entities\LearningPathFolderEntity;
use Symfony\Component\HttpFoundation\Request;

class StructuredDataApi
{
    private IClient $client;

    private const string ENDPOINT = 'api-2.0/structured-data';

    public function __construct(private readonly ClientManager $manager)
    {
        $this->client = $this->manager->getClient(Client::class);
    }

    public function learningPathFolder(string $token, array $payload = [])
    {
        $this->client->setToken($token);
        $payload = array_merge($payload, [
            'page' => 1,
            'page_size' => 100,
        ]);

        $response = $this->client->request(
            Request::METHOD_GET,
            self::ENDPOINT . '/tags/learning_path_folder',
            $payload
        );

        return new LearningPathFolderEntity($response->parseBody()->getData());
    }
}
