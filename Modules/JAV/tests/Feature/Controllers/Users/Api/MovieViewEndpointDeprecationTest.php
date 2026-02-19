<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users\Api;

use Modules\JAV\Models\Jav;
use Modules\JAV\Tests\TestCase;

class MovieViewEndpointDeprecationTest extends TestCase
{
    public function test_legacy_movie_view_endpoint_is_not_available(): void
    {
        $jav = Jav::factory()->create(['views' => 2]);

        $this->postJson("/jav/movies/{$jav->uuid}/view")
            ->assertNotFound();
    }
}
