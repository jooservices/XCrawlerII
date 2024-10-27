<?php

namespace Modules\Core\Tests\Unit\Services;

use Modules\Core\Models\Setting;
use Modules\Core\Services\SettingService;
use Modules\Core\Tests\TestCase;

class SettingServiceTest extends TestCase
{
    public function testGetValue()
    {
        $setting = Setting::factory()->create();
        $service = app(SettingService::class);

        $this->assertEquals(
            $setting->value,
            $service->get($setting->group, $setting->key)
        );

        $this->assertEquals(
            'fake',
            $service->get($this->faker->word, $this->faker->word, 'fake')
        );
    }

    public function testUpdateValue()
    {
        $service = app(SettingService::class);
        $this->assertEquals(
            'fake-value',
            $service->get($this->faker->word, $this->faker->word, 'fake-value')
        );

        $service->set('fake-group', 'fake-key', 'fake-ok');
        $this->assertEquals(
            'fake-ok',
            $service->get('fake-group', 'fake-key', 'fake-ok')
        );
    }
}
