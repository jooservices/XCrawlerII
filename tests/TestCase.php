<?php

namespace Tests;

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

        $this->setUpFaker();

        if ($this->usesRefreshDatabase) {
            $this->runRefreshDatabase();
        }
    }
}
