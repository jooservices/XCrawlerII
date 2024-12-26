<?php

namespace Modules\Udemy\Tests\Unit\Services;

use Modules\Udemy\Exceptions\StudyClassTypeNotFound;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\StudyManager;
use Modules\Udemy\Tests\TestCase;

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
}
