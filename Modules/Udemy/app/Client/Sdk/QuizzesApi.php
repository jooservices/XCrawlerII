<?php

namespace Modules\Udemy\Client\Sdk;

use Exception;
use Modules\Udemy\Client\Dto\AssessmentsDto;
use Modules\Udemy\Models\CurriculumItem;
use Symfony\Component\HttpFoundation\Request;

class QuizzesApi extends AbstractApi
{
    private const string ENDPOINT = 'api-2.0/quizzes';

    /**
     * @throws Exception
     */
    public function assessments(CurriculumItem $curriculumItem): ?AssessmentsDto
    {
        $response = $this->client->request(
            Request::METHOD_GET,
            $this->getEndpoint($curriculumItem->id . '/assessments'),
            [
                'version' => '1',
                'page_size' => 250,
                'fields' => [
                    'assessment' => 'id,assessment_type,prompt,correct_response,section,question_plain,related_lectures',
                ],
                'use_remote_version' => true,
            ]
        );

        return (new AssessmentsDto())->transform($response->parseBody()->getData());
    }

    protected function getEndpoint(string $path): string
    {
        return self::ENDPOINT . '/' . trim($path, '/');
    }
}
