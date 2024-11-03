<?php

namespace Modules\Udemy\Services\Client\Sdk;

use Modules\Client\Interfaces\IClient;
use Modules\Client\Services\ClientManager;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Services\Client\Client;
use Modules\Udemy\Services\Client\Entities\AssessmentsEntity;
use Symfony\Component\HttpFoundation\Request;

class Quizzes
{
    private IClient $client;

    private const string ENDPOINT = 'api-2.0/quizzes';

    public function __construct(private readonly ClientManager $manager)
    {
        $this->client = $this->manager->getClient(Client::class);
    }

    public function assessments(string $token, CurriculumItem $curriculumItem)
    {
        $this->client->setToken($token);

        $response = $this->client->request(
            Request::METHOD_GET,
            self::ENDPOINT . '/' . $curriculumItem->id . '/assessments',
            [
                'version' => '1',
                'page_size' => 250,
                'fields' => [
                    'assessment' => 'id,assessment_type,prompt,correct_response,section,question_plain,related_lectures',
                ],
                'use_remote_version' => true,
            ]
        );

        return new AssessmentsEntity($response->parseBody()->getData());
    }
}
