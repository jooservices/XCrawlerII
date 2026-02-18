<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase {
        refreshDatabase as protected runRefreshDatabase;
    }
    use WithFaker;

    protected bool $usesRefreshDatabase = false;
    protected string $csrfToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFaker();
        Cache::flush();

        if (! isset($this->csrfToken)) {
            $this->csrfToken = Str::random(40);
        }

        if ($this->usesRefreshDatabase) {
            $this->runRefreshDatabase();
        }
    }

    protected function withCsrfHeaders(array $headers = []): array
    {
        $this->withSession(['_token' => $this->csrfToken]);

        if (! array_key_exists('X-CSRF-TOKEN', $headers)) {
            $headers['X-CSRF-TOKEN'] = $this->csrfToken;
        }

        return $headers;
    }

    public function post($uri, array $data = [], array $headers = [])
    {
        return parent::post($uri, $data, $this->withCsrfHeaders($headers));
    }

    public function put($uri, array $data = [], array $headers = [])
    {
        return parent::put($uri, $data, $this->withCsrfHeaders($headers));
    }

    public function patch($uri, array $data = [], array $headers = [])
    {
        return parent::patch($uri, $data, $this->withCsrfHeaders($headers));
    }

    public function delete($uri, array $data = [], array $headers = [])
    {
        return parent::delete($uri, $data, $this->withCsrfHeaders($headers));
    }

    public function postJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return parent::postJson($uri, $data, $this->withCsrfHeaders($headers), $options);
    }

    public function putJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return parent::putJson($uri, $data, $this->withCsrfHeaders($headers), $options);
    }

    public function patchJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return parent::patchJson($uri, $data, $this->withCsrfHeaders($headers), $options);
    }

    public function deleteJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return parent::deleteJson($uri, $data, $this->withCsrfHeaders($headers), $options);
    }
}
