<?php

namespace Modules\JAV\Tests\Unit\Repositories;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\UserLikeNotification;
use Modules\JAV\Repositories\UserLikeNotificationRepository;
use Tests\TestCase;

class UserLikeNotificationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_unread_for_user_returns_only_unread_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $jav = Jav::factory()->create();

        $unread = UserLikeNotification::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'read_at' => null,
        ]);
        UserLikeNotification::factory()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'read_at' => now(),
        ]);
        UserLikeNotification::factory()->create([
            'user_id' => $otherUser->id,
            'jav_id' => $jav->id,
            'read_at' => null,
        ]);

        $rows = app(UserLikeNotificationRepository::class)->unreadForUser($user, 20);

        $this->assertCount(1, $rows);
        $this->assertSame($unread->id, $rows->first()->id);
        $this->assertTrue($rows->first()->relationLoaded('jav'));
    }

    public function test_mark_all_read_for_user_marks_all_unread_rows(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        UserLikeNotification::factory()->create(['user_id' => $user->id, 'jav_id' => $jav->id, 'read_at' => null]);
        UserLikeNotification::factory()->create(['user_id' => $user->id, 'jav_id' => $jav->id, 'read_at' => null]);

        $updated = app(UserLikeNotificationRepository::class)->markAllReadForUser($user);

        $this->assertSame(2, $updated);
        $this->assertSame(0, UserLikeNotification::query()->where('user_id', $user->id)->whereNull('read_at')->count());
    }
}
