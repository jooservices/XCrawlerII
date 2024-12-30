<?php

namespace Modules\Udemy\Tests\Unit\Client;

use Exception;
use Modules\Core\Exceptions\InvalidDtoDataException;
use Modules\Udemy\Client\Dto\CourseCategoriesDto;
use Modules\Udemy\Client\Dto\CourseCategoryDto;
use Modules\Udemy\Client\Dto\CoursesDto;
use Modules\Udemy\Client\Sdk\MeApi;
use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Tests\TestCase;

class TestMeApi extends TestCase
{
    protected MeApi $meApi;

    /**
     * @throws Exception
     */
    final public function testGetSubscribedCoursesCategories(): void
    {
        $this->wish
            ->setToken($this->userToken)
            ->wishSubscribedCoursesCategories()
            ->wish();

        $this->meApi = app(UdemySdk::class)
            ->setToken($this->userToken)
            ->me();

        $this->assertInstanceOf(MeApi::class, $this->meApi);

        $list = $this->meApi
            ->subscribedCoursesCategories();
        $this->assertInstanceOf(CourseCategoriesDto::class, $list);

        $this->assertCount(12, $list->getResults());
        $courseCategoryDto = $list->getResults()->first();
        $this->assertInstanceOf(CourseCategoryDto::class, $courseCategoryDto);
        $this->assertEquals(304, $courseCategoryDto->getId());
        $this->assertEquals(CourseCategoryDto::DTO_NAME, $courseCategoryDto->getClass());
        $this->assertEquals('Design Tools', $courseCategoryDto->getTitle());
    }

    final public function testGetSubscribedCourseCategoriesWithError(): void
    {
        $this->expectException(InvalidDtoDataException::class);
        $this->wish
            ->setToken($this->userToken)
            ->wishSubscribedCoursesCategories(15, true)
            ->wish();

        $this->meApi = app(UdemySdk::class)
            ->setToken($this->userToken)
            ->me();

        $this->assertNull($this->meApi->subscribedCoursesCategories());
    }

    /**
     * @throws Exception
     */
    final public function testGetSubscribedCourses(): void
    {
        $this->wish
            ->setToken($this->userToken)
            ->wishSubscribedCourses()
            ->wish();

        $this->meApi = app(UdemySdk::class)
            ->setToken($this->userToken)
            ->me();

        $this->assertInstanceOf(MeApi::class, $this->meApi);

        $coursesDto = $this->meApi->subscribedCourses();

        $this->assertInstanceOf(CoursesDto::class, $coursesDto);
        $this->assertEquals(90, $coursesDto->getCount());
        $this->assertEquals(90, $coursesDto->getResults()->count());

        $courseDto = $coursesDto->getResults()->first();
        $this->assertFalse($courseDto->isCompleted());
    }

    final public function testGetSubscribedCoursesPaging(): void
    {
        $this->wish
            ->setToken($this->userToken)
            ->wishSubscribedCoursesPaging()
            ->wish();

        $this->meApi = app(UdemySdk::class)
            ->setToken($this->userToken)
            ->me();

        $this->assertInstanceOf(MeApi::class, $this->meApi);

        $coursesDto = $this->meApi->subscribedCourses();

        $this->assertInstanceOf(CoursesDto::class, $coursesDto);
        $this->assertEquals(3, $coursesDto->pages());
        $this->assertEquals(90, $coursesDto->getCount());
        $this->assertEquals(40, $coursesDto->getResults()->count());
    }

    final public function testGetSubscribedCoursesWithError(): void
    {
        $this->expectException(InvalidDtoDataException::class);
        $this->wish
            ->setToken($this->userToken)
            ->wishSubscribedCourses(100, true)
            ->wish();

        $this->meApi = app(UdemySdk::class)
            ->setToken($this->userToken)
            ->me();

        $this->assertNull($this->meApi->subscribedCourses());
    }
}
