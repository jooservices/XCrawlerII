<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\MongoDb\Config;
use Modules\Core\Tests\TestCase;

class ConfigModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_factory_creates_record(): void
    {
        $config = Config::factory()->create([
            'group' => 'core',
            'key' => 'feature_flag',
            'value' => 'on',
        ]);

        $this->assertDatabaseHas('configs', [
            'id' => $config->id,
            'group' => 'core',
            'key' => 'feature_flag',
            'value' => 'on',
        ], 'mongodb');
    }
}
