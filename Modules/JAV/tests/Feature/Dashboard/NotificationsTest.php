<?php

namespace Modules\JAV\Tests\Feature\Dashboard;

use App\Models\User;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\UserLikeNotification;
use Modules\JAV\Tests\TestCase;

class NotificationsTest extends TestCase
{
    public function test_user_can_mark_single_notification_as_read(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        $notification = UserLikeNotification::query()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'dedupe_key' => "user:{$user->id}|jav:{$jav->id}|type:like_match",
            'title' => 'New movie matches your likes',
            'message' => 'message',
            'payload' => ['matched_actors' => ['Actor A']],
        ]);

        $this->actingAs($user)
            ->post(route('jav.notifications.read', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_other_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $jav = Jav::factory()->create();

        $notification = UserLikeNotification::query()->create([
            'user_id' => $otherUser->id,
            'jav_id' => $jav->id,
            'dedupe_key' => "user:{$otherUser->id}|jav:{$jav->id}|type:like_match",
            'title' => 'New movie matches your likes',
            'message' => 'message',
            'payload' => [],
        ]);

        $this->actingAs($user)
            ->post(route('jav.notifications.read', $notification))
            ->assertForbidden();
    }

    public function test_mark_all_read_only_updates_current_user_unread_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $jav = Jav::factory()->create();

        $mineUnread = UserLikeNotification::query()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'dedupe_key' => "user:{$user->id}|jav:{$jav->id}|type:like_match",
            'title' => 'New movie matches your likes',
            'message' => 'message',
            'payload' => [],
        ]);
        $otherUnread = UserLikeNotification::query()->create([
            'user_id' => $otherUser->id,
            'jav_id' => $jav->id,
            'dedupe_key' => "user:{$otherUser->id}|jav:{$jav->id}|type:like_match",
            'title' => 'New movie matches your likes',
            'message' => 'message',
            'payload' => [],
        ]);

        $this->actingAs($user)
            ->post(route('jav.notifications.read-all'))
            ->assertRedirect();

        $this->assertNotNull($mineUnread->fresh()->read_at);
        $this->assertNull($otherUnread->fresh()->read_at);
    }
}
