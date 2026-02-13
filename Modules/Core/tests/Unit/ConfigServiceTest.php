<?php

namespace Modules\Core\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Config;
use Modules\Core\Services\ConfigService;
use Tests\TestCase;

class ConfigServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_set_and_get_config_value()
    {
        $service = new ConfigService;

        $service->set('app', 'name', 'XCrawlerII');

        $this->assertEquals('XCrawlerII', $service->get('app', 'name'));
    }

    public function test_returns_default_value_if_config_not_found()
    {
        $service = new ConfigService;

        $this->assertEquals('default', $service->get('app', 'non_existent', 'default'));
    }

    public function test_can_update_existing_config_value()
    {
        $service = new ConfigService;

        $service->set('app', 'debug', 'true');
        $this->assertEquals('true', $service->get('app', 'debug'));

        $service->set('app', 'debug', 'false');
        $this->assertEquals('false', $service->get('app', 'debug'));
    }

    public function test_can_set_description()
    {
        $service = new ConfigService;

        $service->set('app', 'env', 'production', 'Application Environment');

        $config = Config::where('group', 'app')->where('key', 'env')->first();

        $this->assertEquals('Application Environment', $config->description);
    }

    public function test_update_value_preserves_description_if_not_provided()
    {
        $service = new ConfigService;

        $service->set('app', 'env', 'production', 'Initial Description');
        $service->set('app', 'env', 'staging');

        $config = Config::where('group', 'app')->where('key', 'env')->first();

        $this->assertEquals('staging', $config->value);
        $this->assertEquals('Initial Description', $config->description);
    }

    public function test_can_update_description()
    {
        $service = new ConfigService;

        $service->set('app', 'env', 'production', 'Initial Description');
        $service->set('app', 'env', 'production', 'Updated Description');

        $config = Config::where('group', 'app')->where('key', 'env')->first();

        $this->assertEquals('Updated Description', $config->description);
    }
}
