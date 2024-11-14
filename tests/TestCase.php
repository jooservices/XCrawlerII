<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Client\Models\RequestLog;
use Modules\Core\Models\Setting;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setUp(): void
    {
        parent::setUp();

        RequestLog::truncate();
        Setting::truncate();
    }
}
