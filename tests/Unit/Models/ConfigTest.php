<?php

namespace Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Config;
use Tests\TestCase;

class ConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_can_be_created(): void
    {
        $config = Config::factory()->create([
            'group' => 'jav',
            'key' => 'show_cover',
            'value' => 'false',
        ]);

        $this->assertDatabaseHas('configs', [
            'id' => $config->id,
            'group' => 'jav',
            'key' => 'show_cover',
        ]);
    }
}
