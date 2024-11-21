<?php

namespace Modules\Udemy\Tests\Unit\Client;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Modules\Udemy\Client\Dto\CourseCategoriesDto;
use Modules\Udemy\Client\Dto\CourseCategoryDto;
use Modules\Udemy\Client\Dto\CoursesDto;
use Modules\Udemy\Client\Sdk\MeApi;
use Modules\Udemy\Tests\TestCase;

class TestMeApi extends TestCase
{
    protected MeApi $meApi;

    /**
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->meApi = $this->udemySdk->me();
    }

    /**
     * @throws Exception
     */
    public function test_get_subscribed_courses_categories()
    {
        $this->assertInstanceOf(MeApi::class, $this->meApi);

        $list = $this->meApi->subscribedCoursesCategories();
        $this->assertInstanceOf(CourseCategoriesDto::class, $list);

        $this->assertCount(12, $list->getResults());
        $courseCategoryDto = $list->getResults()->first();
        $this->assertInstanceOf(CourseCategoryDto::class, $courseCategoryDto);
        $this->assertEquals(304, $courseCategoryDto->getId());
        $this->assertEquals(CourseCategoryDto::DTO_NAME, $courseCategoryDto->getClass());
        $this->assertEquals('Design Tools', $courseCategoryDto->getTitle());

        $this->assertNull(
            $this->meApi->subscribedCoursesCategories(['error' => 403])
        );
    }

    /**
     * @throws Exception
     */
    public function test_get_subscribed_courses()
    {
        $this->assertInstanceOf(MeApi::class, $this->udemySdk->me());

        $coursesDto = $this->meApi->subscribedCourses();
        $this->assertInstanceOf(CoursesDto::class, $coursesDto);
        $this->assertEquals(90, $coursesDto->getCount());
        $this->assertEquals(90, $coursesDto->getResults()->count());

        $courseDto = $coursesDto->getResults()->first();
        $this->assertFalse($courseDto->isCompleted());

        $this->assertNull(
            $this->meApi->subscribedCourses(['error' => 403])
        );

        $list = $this->meApi->subscribedCourses(['page' => 1, 'page_size' => 40]);
        $this->assertEquals(3, $list->pages());
        $list = $this->meApi->subscribedCourses(['page' => 3, 'page_size' => 40]);
        $this->assertEquals(10, $list->getResults()->count());
    }
}
