<?php

namespace Modules\Udemy\Tests\Unit\Client;

use Modules\Udemy\Client\Dto\EnrollmentDto;
use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Tests\TestCase;

class TestLearningPaths extends TestCase
{
    public function testEnrollment(): void
    {
        $userToken = UserToken::factory()->create();
        $this->wish->setToken($userToken)
            ->wishEnrollment(8638201)
            ->wish();

        $sdk = app(UdemySdk::class)
            ->setToken($userToken)
            ->learningPaths();

        $this->assertInstanceOf(
            EnrollmentDto::class,
            $sdk->enrollment(8638201)
        );
    }
}
