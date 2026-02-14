<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users\Api;

use Modules\JAV\Models\Jav;
use Modules\JAV\Tests\TestCase;

class MovieControllerContractTest extends TestCase
{
    public function test_movie_view_endpoint_returns_views_and_handles_missing_movie(): void
    {
        $jav = Jav::factory()->create(['views' => 2]);

        $this->postJson(route('jav.movies.view', $jav))
            ->assertOk()
            ->assertJsonStructure(['views'])
            ->assertJsonPath('views', 3);

        $this->postJson('/jav/movies/999999/view')->assertNotFound();
    }
}
