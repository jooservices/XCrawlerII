<?php

namespace Modules\JAV\Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\UserJavHistory;
use Tests\TestCase;

class UserJavHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $history = UserJavHistory::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $history->user);
        $this->assertSame($user->id, $history->user->id);
    }

    public function test_history_belongs_to_jav(): void
    {
        $jav = Jav::factory()->create();
        $history = UserJavHistory::factory()->create(['jav_id' => $jav->id]);

        $this->assertInstanceOf(Jav::class, $history->jav);
        $this->assertSame($jav->id, $history->jav->id);
    }
}
