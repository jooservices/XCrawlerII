<?php

namespace Modules\Jav\Tests\Unit\Services;

use Modules\Jav\app\Services\OnejavService;
use Modules\Jav\tests\TestCase;

class OnejavServiceTest extends TestCase
{
    public function testNew(): void
    {
        $service = app(OnejavService::class);
        $service->new();

        $this->assertDatabaseHas(
            'settings',
            [
                'group' => 'onejav',
                'key' => 'new_last_page',
                'value' => 4,
            ],
            'mongodb'
        );
        $this->assertDatabaseHas(
            'settings',
            [
                'group' => 'onejav',
                'key' => 'new_current_page',
                'value' => 2,
            ],
            'mongodb'
        );
        $this->assertDatabaseCount('onejav',10, 'mongodb');

        $service->new();
        $this->assertDatabaseHas(
            'settings',
            [
                'group' => 'onejav',
                'key' => 'new_current_page',
                'value' => 3,
            ],
            'mongodb'
        );
    }

    public function testPopular(): void
    {
        $service = app(OnejavService::class);
        $service->popular();

        $this->assertDatabaseHas(
            'settings',
            [
                'group' => 'onejav',
                'key' => 'popular_last_page',
                'value' => 4,
            ],
            'mongodb'
        );
        $this->assertDatabaseHas(
            'settings',
            [
                'group' => 'onejav',
                'key' => 'popular_current_page',
                'value' => 2,
            ],
            'mongodb'
        );

        $service->popular();
        $this->assertDatabaseHas(
            'settings',
            [
                'group' => 'onejav',
                'key' => 'popular_current_page',
                'value' => 3,
            ],
            'mongodb'
        );
    }
}
