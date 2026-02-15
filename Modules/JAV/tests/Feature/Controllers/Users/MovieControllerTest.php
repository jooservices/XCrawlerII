<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users;

use Modules\JAV\Models\Jav;
use Modules\JAV\Tests\TestCase;

class MovieControllerTest extends TestCase
{
    public function test_download_returns_back_with_error_for_unsupported_source_and_increments_downloads(): void
    {
        $jav = Jav::factory()->create([
            'source' => 'unsupported-source',
            'downloads' => 3,
            'url' => 'https://example.com/unsupported',
        ]);

        $response = $this->from(route('jav.vue.dashboard'))
            ->get(route('jav.movies.download', $jav));

        $response
            ->assertRedirect(route('jav.vue.dashboard'))
            ->assertSessionHas('error');

        $this->assertSame(4, $jav->fresh()->downloads);
    }
}
