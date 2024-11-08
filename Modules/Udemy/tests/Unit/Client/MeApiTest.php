<?php

namespace Modules\Udemy\Tests\Unit\Client;

use Exception;
use Modules\Udemy\Services\Client\Entities\CourseCategoryEntity;
use Modules\Udemy\Services\Client\Entities\CoursesCategoriesEntity;
use Modules\Udemy\Services\Client\Entities\CoursesEntity;
use Modules\Udemy\Services\Client\Sdk\MeApi;
use Modules\Udemy\Services\Client\UdemySdk;
use Modules\Udemy\Tests\TestCase;

class MeApiTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testGetSubscribedCoursesCategories()
    {
        $me = $this->udemySdk->me();
        $this->assertInstanceOf(MeApi::class, $me);

        $list = $me->subscribedCoursesCategories($this->faker->uuid);

        $this->assertInstanceOf(CoursesCategoriesEntity::class, $list);
        $this->assertCount(12, $list->getResults());

        $categoryEntity = $list->getResults()->first();
        $this->assertInstanceOf(CourseCategoryEntity::class, $categoryEntity);
        $this->assertEquals(304, $categoryEntity->getId());
        $this->assertEquals(CourseCategoryEntity::CLASS_NAME, $categoryEntity->getClass());
        $this->assertEquals('Design Tools', $categoryEntity->getTitle());

        $this->assertNull(
            $me->subscribedCoursesCategories($this->faker->uuid, ['error' => 403])
        );
    }

    /**
     * @throws Exception
     */
    public function testGetSubscribedCourses()
    {
        $me = $this->udemySdk->me();
        $this->assertInstanceOf(MeApi::class, $me);

        $list = $me->subscribedCourses($this->faker->uuid);
        $this->assertInstanceOf(CoursesEntity::class, $list);

        $this->assertNull(
            $me->subscribedCourses($this->faker->uuid, ['error' => 403])
        );

        $list = $me->subscribedCourses($this->faker->uuid, ['page' => 1, 'page_size' => 40]);
        $this->assertEquals(3, $list->pages());

        $list = $me->subscribedCourses($this->faker->uuid, ['page' => 3, 'page_size' => 40]);
        $this->assertEquals(10, $list->getResults()->count());
    }
}
