<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users;

use Modules\JAV\Models\Jav;
use Modules\JAV\Models\UserJavHistory;
use Modules\JAV\Tests\TestCase;

class MovieDownloadGuestBehaviorTest extends TestCase
{
    public function test_guest_cannot_download_movie(): void
    {
        $jav = Jav::factory()->create([
            'source' => 'unsupported-source',
            'downloads' => 0,
        ]);

        $this->from(route('jav.vue.dashboard'))
            ->get(route('jav.movies.download', $jav))
            ->assertRedirect(route('login'));

        $this->assertSame(0, (int) $jav->fresh()->downloads);

        $historyCount = UserJavHistory::query()
            ->where('jav_id', $jav->id)
            ->where('action', 'download')
            ->count();

        $this->assertSame(0, $historyCount);
    }
}
