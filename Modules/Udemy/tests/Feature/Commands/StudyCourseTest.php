<?php

namespace Modules\Udemy\Tests\Feature\Commands;

use Exception;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Tests\TestCase;

class StudyCourseTest extends TestCase
{
    final public function testInvalid(): void
    {
        $this->expectException(Exception::class);
        $userToken = UserToken::factory()->create();
        $this->artisan('udemy:study-course')
            ->expectsQuestion('Enter your Udemy token', $userToken->token)
            ->expectsQuestion('Choose a course to study', 1);
    }

    final public function testSuccess(): void
    {
    }
}
