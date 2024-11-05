<?php

namespace Modules\Udemy\Tests\Feature\Controllers;

use Illuminate\Support\Facades\Bus;
use Modules\Udemy\Jobs\SyncMyCoursesJob;
use Modules\Udemy\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class UdemyControllerTest extends TestCase
{
    public function testSuccess()
    {
        Bus::fake([
            SyncMyCoursesJob::class,
        ]);

        $token = $this->faker->uuid;
        $this->post('api/v1/udemy/users', [
            'token' => $token,
        ])->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'data' => [
                    'token' => $token,
                ],
            ]);
    }
}
