<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase {
        refreshDatabase as protected runRefreshDatabase;
    }
    use WithFaker;

    protected bool $usesRefreshDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Keep test execution deterministic regardless of .env.testing overrides.
        config([
            'queue.default' => 'sync',
            'cache.default' => 'array',
        ]);

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->setUpFaker();

        if ($this->usesRefreshDatabase) {
            $this->runRefreshDatabase();
        }
    }
}
