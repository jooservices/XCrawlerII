<?php

namespace Modules\Udemy\Client\Sdk;

use Modules\Udemy\Client\Dto\CourseCurriculumItemsDto;
use Symfony\Component\HttpFoundation\Request;

class CoursesApi extends AbstractApi
{
    public const string ENDPOINT = 'api-2.0/courses';

    final public function subscriberCurriculumItems(
        int $courseId,
        array $payload = []
    ): CourseCurriculumItemsDto {
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
            $this->getEndpoint($courseId . '/subscriber-curriculum-items'),
            $payload
        );

        /**
         * @TODO Handle not success
         */

        return (new CourseCurriculumItemsDto())->transform($response->parseBody()->getData());
    }

    protected function getEndpoint(string $path): string
    {
        return self::ENDPOINT . '/' . trim($path, '/');
    }
}
