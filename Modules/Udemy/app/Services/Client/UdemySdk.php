<?php

namespace Modules\Udemy\Services\Client;

use Modules\Udemy\Services\Client\Sdk\Courses;
use Modules\Udemy\Services\Client\Sdk\MeApi;
use Modules\Udemy\Services\Client\Sdk\Quizzes;
use Modules\Udemy\Services\Client\Sdk\StructuredDataApi;

class UdemySdk
{
    public function me(): MeApi
    {
        return app(MeApi::class);
    }

    public function structuredData(): StructuredDataApi
    {
        return app(StructuredDataApi::class);
    }

    public function courses(): Courses
    {
        return app(Courses::class);
    }

    public function quizzes(): Quizzes
    {
        return app(Quizzes::class);
    }
}
