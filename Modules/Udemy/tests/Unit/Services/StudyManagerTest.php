<?php

namespace Modules\Udemy\Tests\Unit\Services;

use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Modules\Udemy\Client\Client;
use Modules\Udemy\Exceptions\StudyClassTypeNotFound;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Services\StudyManager;
use Modules\Udemy\Tests\TestCase;
use Modules\Udemy\Zeus\Wishes\UdemyWish;

class StudyManagerTest extends TestCase
{
    final public function testInvalidStudy(): void
    {
        $this->expectException(StudyClassTypeNotFound::class);

        $curriculumItem = CurriculumItem::factory()->create([
            'type' => 'fake',
        ]);

        $manager = app(StudyManager::class);
        $manager->study($this->userToken, $curriculumItem);
    }

    final public function testValidStudy(): void
    {
        $curriculumItem = CurriculumItem::factory()->create([
            'type' => 'article',
            'asset_type' => 'article',
        ]);

        app(UdemyWish::class)
            ->setToken($this->userToken)
            ->wish(function (MockInterface $mock) use ($curriculumItem) {
                $response = new Response(
                    201,
                    [
                        'Content-Type' => Client::CONTENT_TYPE,
                    ],
                    ''
                );

                $mock->expects('request')
                    ->withSomeOfArgs(
                        Request::METHOD_POST,
                        UdemyWish::ME_SUBSCRIBED_COURSES
                        . '/' . $curriculumItem->course->id
                        . '/lectures/' . $curriculumItem->id . '/view-logs'
                    )
                    ->andReturn($response);

                $mock->expects('request')
                    ->withSomeOfArgs(
                        Request::METHOD_POST,
                        UdemyWish::ME_SUBSCRIBED_COURSES
                        . '/' . $curriculumItem->course->id
                        . '/lectures/' . $curriculumItem->id . '/progress-logs'
                    )
                    ->andReturn($response);

                $mock->expects('request')
                    ->withSomeOfArgs(
                        Request::METHOD_POST,
                        UdemyWish::ME_SUBSCRIBED_COURSES
                        . '/' . $curriculumItem->course->id
                        . '/completed-lectures',
                    )
                    ->andReturn($response);

                return $mock;
            });

        $manager = app(StudyManager::class);
        $manager->study($this->userToken, $curriculumItem);
    }
}
