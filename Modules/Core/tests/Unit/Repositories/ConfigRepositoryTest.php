<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\MongoDb\Config;
use Modules\Core\Repositories\ConfigRepository;
use Modules\Core\Tests\TestCase;

class ConfigRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ConfigRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ConfigRepository;
    }

    public function test_get_returns_null_when_not_found(): void
    {
        $this->assertNull($this->repository->get('non_existent', 'key'));
    }

    public function test_get_returns_config_model_when_found(): void
    {
        Config::factory()->create([
            'group' => 'app',
            'key' => 'name',
            'value' => 'XCrawler',
        ]);

        $config = $this->repository->get('app', 'name');

        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals('XCrawler', $config->value);
    }

    public function test_update_or_create_creates_new_record(): void
    {
        $config = $this->repository->updateOrCreate('app', 'timezone', 'UTC', 'System Timezone');

        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals('app', $config->group);
        $this->assertEquals('timezone', $config->key);
        $this->assertEquals('UTC', $config->value);
        $this->assertEquals('System Timezone', $config->description);

        $this->assertDatabaseHas('configs', [
            'group' => 'app',
            'key' => 'timezone',
            'value' => 'UTC',
        ], 'mongodb');
    }

    public function test_update_or_create_updates_existing_record_and_preserves_description_if_null(): void
    {
        Config::factory()->create([
            'group' => 'app',
            'key' => 'debug',
            'value' => 'false',
            'description' => 'Debug Mode',
        ]);

        $config = $this->repository->updateOrCreate('app', 'debug', 'true');

        $this->assertEquals('true', $config->value);
        $this->assertEquals('Debug Mode', $config->description);
    }
}
