<?php

namespace Modules\Udemy\Tests\Unit\Client;

use Modules\Udemy\Services\Client\Entities\CourseCategoryEntity;
use Modules\Udemy\Services\Client\Entities\CoursesCategoriesEntity;
use Modules\Udemy\Services\Client\Sdk\MeApi;
use Modules\Udemy\Services\Client\UdemySdk;
use Modules\Udemy\Tests\TestCase;

class UdemySdkTest extends TestCase
{
    public function testGetSubscribedCoursesCategoriesSuccess()
    {
        $me = app(UdemySdk::class)->me();
        $this->assertInstanceOf(MeApi::class, $me);

        $list = $me->subscribedCoursesCategories($this->faker->uuid);

        $this->assertInstanceOf(CoursesCategoriesEntity::class, $list);
        $this->assertCount(12, $list->getResults());

        $categoryEntity = $list->getResults()->first();
        $this->assertInstanceOf(CourseCategoryEntity::class, $categoryEntity);
        $this->assertEquals(304, $categoryEntity->getId());
        $this->assertEquals(CourseCategoryEntity::CLASS_NAME, $categoryEntity->getClass());
        $this->assertEquals('Design Tools', $categoryEntity->getTitle());
    }
}
