<?php

namespace Modules\Udemy\Services\Client;

use Modules\Udemy\Services\Client\Sdk\Courses;
use Modules\Udemy\Services\Client\Sdk\MeApi;
use Modules\Udemy\Services\Client\Sdk\Quizzes;
use Modules\Udemy\Services\Client\Sdk\StructuredDataApi;

class UdemySdk
{
    public function me()
    {
        return app(MeApi::class);
    }

    public function structuredData()
    {
        return app(StructuredDataApi::class);
    }

    public function courses()
    {
        return app(Courses::class);
    }

    public function quizzes()
    {
        return app(Quizzes::class);
    }
}
