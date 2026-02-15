<?php

namespace Modules\JAV\Tests;

use App\Models\User;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Contracts\Auth\Authenticatable;
use Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected bool $usesRefreshDatabase = true;

    protected function loadFixture(string $path): string
    {
        return file_get_contents(__DIR__.'/Fixtures/'.$path);
    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function actingAsUser(array $attributes = []): User
    {
        $user = $this->createUser($attributes);
        $this->actingAs($this->asAuthenticatable($user));

        return $user;
    }

    protected function asAuthenticatable(User $user): Authenticatable
    {
        assert($user instanceof Authenticatable);

        return $user;
    }

    protected function getMockResponse(string $path): \JOOservices\Client\Response\ResponseWrapper
    {
        $content = $this->loadFixture($path);
        $psrResponse = new PsrResponse(200, [], Utils::streamFor($content));

        return new \JOOservices\Client\Response\ResponseWrapper($psrResponse);
    }
}
