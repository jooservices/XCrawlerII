<?php

namespace Modules\Udemy\Client\Sdk;

use Exception;
use JsonException;
use Modules\Udemy\Client\Dto\EnrollmentDto;
use Symfony\Component\HttpFoundation\Request;

class LearningPaths extends AbstractApi
{
    public const string ENDPOINT = 'api-2.0/learning-paths';

    /**
     * @throws JsonException
     * @throws Exception
     */
    final public function enrollment(int $pathId, array $payload = []): EnrollmentDto
    {
        $payload = array_merge($payload, [
            'is_auto_enroll' => true,
        ]);

        $response = $this->client->request(
            Request::METHOD_POST,
            $this->getEndpoint($pathId . '/enrollments'),
            $payload
        );

        return (new EnrollmentDto())->transform($response->parseBody()->getData());
    }

    protected function getEndpoint(string $path): string
    {
        return self::ENDPOINT . '/' . trim($path, '/');
    }
}
