<?php

namespace Modules\Core\Tests\Unit\Services;

use Modules\Core\Models\Setting;
use Modules\Core\Tests\TestCase;
use Modules\Core\Facades\Setting as SettingFacade;

class SettingServiceTest extends TestCase
{
    public function testGetValue()
    {
        /**
         * @var Setting $setting
         */
        $setting = Setting::factory()->create();

        $this->assertEquals(
            $setting->value,
            SettingFacade::get($setting->group, $setting->key)
        );

        $this->assertEquals(
            'fake',
            SettingFacade::get($this->faker->word, $this->faker->word, 'fake')
        );
    }

    public function testUpdateValue()
    {
        $this->assertEquals(
            'fake-value',
            SettingFacade::get($this->faker->word, $this->faker->word, 'fake-value')
        );

        SettingFacade::set('fake-group', 'fake-key', 'fake-ok');
        $this->assertEquals(
            'fake-ok',
            SettingFacade::get('fake-group', 'fake-key', 'fake-ok')
        );
    }
}
