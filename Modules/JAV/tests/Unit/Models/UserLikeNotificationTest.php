<?php

namespace Modules\JAV\Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\UserLikeNotification;
use Tests\TestCase;

class UserLikeNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $notification = UserLikeNotification::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $notification->user);
        $this->assertSame($user->id, $notification->user->id);
    }

    public function test_notification_belongs_to_jav(): void
    {
        $jav = Jav::factory()->create();
        $notification = UserLikeNotification::factory()->create(['jav_id' => $jav->id]);

        $this->assertInstanceOf(Jav::class, $notification->jav);
        $this->assertSame($jav->id, $notification->jav->id);
    }

    public function test_unread_scope_only_returns_unread_rows(): void
    {
        UserLikeNotification::factory()->create(['read_at' => null]);
        UserLikeNotification::factory()->create(['read_at' => now()]);

        $this->assertSame(1, UserLikeNotification::unread()->count());
    }
}
