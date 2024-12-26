<?php

namespace Modules\Udemy\Tests\Unit\Services;

use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Modules\Core\Zeus\ZeusService;
use Modules\Udemy\Client\Client;
use Modules\Udemy\Exceptions\StudyClassTypeNotFound;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\StudyManager;
use Modules\Udemy\Tests\TestCase;
use Modules\Udemy\Zeus\Wishes\UdemyWish;

class StudyManagerTest extends TestCase
{
    final public function testInvalidStudy(): void
    {
        $this->expectException(StudyClassTypeNotFound::class);
        $userToken = UserToken::factory()->create();
        $curriculumItem = CurriculumItem::factory()->create([
            'type' => 'fake',
        ]);

        $manager = app(StudyManager::class);
        $manager->study($userToken, $curriculumItem);
    }

    final public function testValidStudy(): void
    {
        $this->expectNotToPerformAssertions();
        $userToken = UserToken::factory()->create();
        $curriculumItem = CurriculumItem::factory()->create([
            'type' => 'article',
        ]);

        app(ZeusService::class)->wish(
            UdemyWish::class,
            function (MockInterface $mock) use ($curriculumItem) {
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
                        'api-2.0/users/me/subscribed-courses/'
                        . $curriculumItem->course->id
                        . '/lectures/' . $curriculumItem->id . '/view-logs'
                    )
                    ->andReturn($response);

                $mock->expects('request')
                    ->withSomeOfArgs(
                        Request::METHOD_POST,
                        'api-2.0/users/me/subscribed-courses/' . $curriculumItem->course->id
                        . '/lectures/' . $curriculumItem->id . '/progress-logs'
                    )
                    ->andReturn($response);

                $mock->expects('request')
                    ->withSomeOfArgs(
                        Request::METHOD_POST,
                        'api-2.0/users/me/subscribed-courses/' . $curriculumItem->course->id
                        . '/completed-lectures',
                    )
                    ->andReturn($response);

                return $mock;
            }
        );

        $manager = app(StudyManager::class);
        $manager->study($userToken, $curriculumItem);
    }
}
