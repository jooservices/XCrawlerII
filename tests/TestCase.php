<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
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

    protected function assertSetting(string $group, string $key, mixed $value): void
    {
        $this->assertDatabaseHas(
            'settings',
            [
                'group' => $group,
                'key' => Str::slug($key, '_'),
                'value' => $value,
            ],
            'mongodb'
        );
    }
}
