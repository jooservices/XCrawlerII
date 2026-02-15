<?php

namespace Modules\JAV\Tests\Feature\Controllers\Users\Api;

use App\Models\User;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\UserLikeNotification;
use Modules\JAV\Tests\TestCase;

class NotificationControllerEdgeTest extends TestCase
{
    public function test_mark_notification_read_returns_not_found_for_missing_notification(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/jav/api/notifications/999999/read')
            ->assertNotFound();
    }

    public function test_index_returns_empty_items_for_user_without_notifications(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('jav.api.notifications.index'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 0)
            ->assertJsonPath('items', []);
    }

    public function test_mark_all_read_keeps_already_read_notifications_unchanged(): void
    {
        $user = User::factory()->create();
        $jav = Jav::factory()->create();

        $readNotification = UserLikeNotification::query()->create([
            'user_id' => $user->id,
            'jav_id' => $jav->id,
            'dedupe_key' => "user:{$user->id}|jav:{$jav->id}|type:like_match:read",
            'title' => 'Read',
            'message' => 'Already read',
            'payload' => [],
            'read_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)
            ->postJson(route('jav.api.notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($readNotification->fresh()->read_at);
    }
}
