<?php

namespace Modules\Udemy\Services\Client\Sdk;

use Modules\Client\Interfaces\IClient;
use Modules\Client\Services\ClientManager;
use Modules\Udemy\Services\Client\Client;
use Modules\Udemy\Services\Client\Entities\CourseCurriculumItemsEntity;
use Symfony\Component\HttpFoundation\Request;

class Courses
{
    private IClient $client;

    private const string ENDPOINT = 'api-2.0/courses';

    public function __construct(private readonly ClientManager $manager)
    {
        $this->client = $this->manager->getClient(Client::class);
    }

    public function subscriberCurriculumItems(
        string $token,
        int $courseId,
        array $payload = []
    ): CourseCurriculumItemsEntity {
        $this->client->setToken($token);
        $payload = array_merge($payload, [
            'fields' => [
                'lecture' => 'title,object_index,is_published,sort_order,created,asset,supplementary_assets,is_free',
                'quiz' => 'title,object_index,is_published,sort_order,type',
                'practice' => 'title,object_index,is_published,sort_order',
                'chapter' => 'title,object_index,is_published,sort_order',
                'asset' => 'title,filename,asset_type,status,time_estimation,is_external',
            ],
            'caching_intent' => 'True',
            'page_size' => 200,
        ]);

        $response = $this->client->request(
            Request::METHOD_GET,
            self::ENDPOINT . '/' . $courseId . '/subscriber-curriculum-items',
            $payload
        );

        return new CourseCurriculumItemsEntity($response->parseBody()->getData());
    }
}
