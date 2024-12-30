<?php

namespace Modules\Udemy\Tests\Unit\Zeus;

use Modules\Core\Exceptions\InvalidDtoDataException;
use Modules\Udemy\Client\Dto\CourseCategoriesDto;
use Modules\Udemy\Client\Dto\CoursesDto;
use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Tests\TestCase;
use Modules\Udemy\Zeus\Wishes\UdemyWish;

class TestUdemyWish extends TestCase
{
    final public function testWishSubscribedCoursesCategories(): void
    {
        $wish = app(UdemyWish::class);
        $wish
            ->setToken($this->userToken)
            ->wishSubscribedCoursesCategories()
            ->wish();

        $subscribedCourses = app(UdemySdk::class)
            ->setToken($this->userToken)
            ->me()->subscribedCoursesCategories();

        $this->assertInstanceOf(CourseCategoriesDto::class, $subscribedCourses);
    }

    final public function testWishSubscribedCourseCategoryError(): void
    {
        $this->expectException(InvalidDtoDataException::class);
        $wish = app(UdemyWish::class);
        $wish->setToken($this->userToken)
            ->wishSubscribedCoursesCategories(15, true)
            ->wish();

        app(UdemySdk::class)
            ->setToken($this->userToken)
            ->me()->subscribedCoursesCategories();
    }

    final public function testWishSubscribedCourses(): void
    {
        $wish = app(UdemyWish::class);
        $wish
            ->setToken($this->userToken)
            ->wishSubscribedCourses()
            ->wish();

        $subscribedCourses = app(UdemySdk::class)
            ->setToken($this->userToken)
            ->me()->subscribedCourses();

        $this->assertInstanceOf(CoursesDto::class, $subscribedCourses);
    }

    final public function testWishSubscribedCoursesError(): void
    {
        $this->expectException(InvalidDtoDataException::class);
        $wish = app(UdemyWish::class);
        $wish->setToken($this->userToken)
            ->wishSubscribedCourses(100, true)
            ->wish();

        app(UdemySdk::class)
            ->setToken($this->userToken)
            ->me()->subscribedCourses();
    }

    final public function testWishSubscribedCoursesPaging(): void
    {
        $wish = app(UdemyWish::class);
        $wish->setToken($this->userToken)
            ->wishSubscribedCoursesPaging()
            ->wish();

        $courses = app(UdemySdk::class)
            ->setToken($this->userToken)
            ->me()->subscribedCourses([
                'page' => 1,
            ]);

        $this->assertInstanceOf(CoursesDto::class, $courses);
        $this->assertCount(40, $courses->getResults());
        $this->assertEquals(3, $courses->pages());

        $courses = app(UdemySdk::class)
            ->setToken($this->userToken)
            ->me()->subscribedCourses([
                'page' => 2,
            ]);

        $this->assertCount(40, $courses->getResults());

        $courses = app(UdemySdk::class)
            ->setToken($this->userToken)
            ->me()->subscribedCourses([
                'page' => 3,
            ]);

        $this->assertCount(10, $courses->getResults());
    }

    final public function testWishSubscribedCoursesCategoriesWithNullBody(): void
    {
        $this->userToken = UserToken::factory()->create();
        $wish = app(UdemyWish::class);
        $wish->setToken($this->userToken)
            ->wishSubscribedCourses(100, false, null)
            ->wish();

        $coursesDto = app(UdemySdk::class)
            ->setToken($this->userToken)
            ->me()->subscribedCourses();

        $this->assertInstanceOf(CoursesDto::class, $coursesDto);
    }
}
